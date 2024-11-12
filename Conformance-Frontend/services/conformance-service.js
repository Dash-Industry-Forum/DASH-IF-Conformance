const {wait} = Tools;

const ConformanceService = (function () {
	const modules = [
		{
			id: 'segment-validation',
			text: 'Segment Validation',
			queryParam: 'segments',
			m3u8Compatible: true,
		},
		{
			id: 'dash-if',
			text: 'DASH-IF',
			queryParam: 'dash',
			m3u8Compatible: false,
		},
		{
			id: 'cmaf', text: 'CMAF', queryParam: 'cmaf', m3u8Compatible: true,
		},
		{
			id: 'cta-wave',
			text: 'CTA-WAVE',
			queryParam: 'ctawave',
			m3u8Compatible: true,
		},
		{
			id: 'wavehlsinterop',
			text: 'CTA-WAVE DASH-HLS Interoperability (CTA-5005-A)',
			queryParam: 'wavehlsinterop',
			m3u8Compatible: false,
		},
		{
			id: 'hbbtv', text: 'HbbTV', queryParam: 'hbbtv', m3u8Compatible: false,
		},
		{
			id: 'latest_xsd',
			text: 'Latest XSD',
			queryParam: 'latest_xsd',
			m3u8Compatible: false,
		},
		{
			id: 'dvb',
			text: 'DVB (2018 xsd)',
			queryParam: 'dvb',
			m3u8Compatible: false,
		},
		{
			id: 'dvb2019',
			text: 'DVB (2019 xsd)',
			queryParam: 'dvb_2019',
			m3u8Compatible: false,
		},
		{
			id: 'dash-if-ll',
			text: 'DASH-IF IOP Low Latency',
			queryParam: 'lowlatency',
			m3u8Compatible: false,
		},
		{
			id: 'dash-if-iop',
			text: 'DASH-IF Interoperability',
			queryParam: 'iop',
			m3u8Compatible: false,
		},
		{
			id: 'dolby', text: 'Dolby', queryParam: 'dolby', m3u8Compatible: false,
		},
		{
			id: 'autodetect',
			text: 'Automatically detect profiles',
			queryParam: 'autodetect',
			m3u8Compatible: true,
		},
		{
			id: 'disable-detailed-segment-output',
			text: 'Disable detailed segment validation output',
			queryParam: 'disable_detailed_segment_output',
			m3u8Compatible: false,
		},
	];

	const BASE_URI = '/Utils/Process_cli.php?';

	async function validateContentByUrl({mpdUrl, activeModules}) {
		let uri = BASE_URI + `url=${mpdUrl}&`;
		for (const module of modules) {
			if (!module.queryParam) {
				continue;
			}

			uri
        += `${module.queryParam}=${activeModules[module.id] ? '1' : '0'}&`;
		}

		let results = await Net.sendRequest({method: 'GET', uri});
		results = JSON.parse(results);
		results = convertInfoData(results);
		return results;
	}

	async function validateContentByText({mpdText, activeModules}) {
		mpdText = encodeURIComponent(mpdText);
		let data = `mpd=${mpdText}&`;
		for (const module of modules) {
			if (!module.queryParam) {
				continue;
			}

			data
        += `${module.queryParam}=${activeModules[module.id] ? '1' : '0'}&`;
		}

		let results = await Net.sendRequest({
			method: 'POST',
			uri: BASE_URI,
			data,
			headers: {
				'Content-type': 'application/x-www-form-urlencoded',
			},
		});
		results = JSON.parse(results);
		results = convertInfoData(results);
		return results;
	}

	async function validateContentByFile({mpdFile, activeModules}) {
		const data = new FormData();
		data.append('mpd', mpdFile);
		for (const module of modules) {
			if (!module.queryParam) {
				continue;
			}

			data.append(module.queryParam, activeModules[module.id] ? '1' : '0');
		}

		let results = await Net.sendRequest({
			method: 'POST',
			uri: BASE_URI,
			data,
		});
		results = JSON.parse(results);
		results = convertInfoData(results);
		return results;
	}

	function convertInfoData(result) {
		const moduleNames = Object.keys(result.entries).filter(
			key => key !== 'Stats' && key !== 'verdict',
		);
		const modules = moduleNames.map(name => {
			const module = result.entries[name];
			module.name = name;
			return module;
		});

		for (const module of modules) {
			const partNames = Object.keys(module).filter(
				key => typeof module[key] === 'object' && 'test' in module[key],
			);
			const parts = partNames.map(partName => {
				const part = module[partName];
				part.name = partName;
				return part;
			});

			for (const part of parts) {
				if (!part.info) {
					continue;
				}

				const {info} = part;
				info.forEach(info => {
					const xmlStart = info.split('').indexOf('<');
					if (xmlStart === -1) {
						return;
					}

					let xmlEnd = info
						.split('')
						.reverse()
						.indexOf('>');
					xmlEnd = info.length - xmlEnd;
					let xmlString = info.substring(xmlStart, xmlEnd);
					xmlString = xmlString.replaceAll(/svrl:/gi, 'svrl_');
					xmlString = `<results>${xmlString}</results>`;
					const parser = new DOMParser();
					const xml = parser.parseFromString(xmlString, 'text/xml');
					const xmlTestResults = Array.from(
						xml.querySelectorAll('svrl_failed-assert'),
					);
					for (const [index, xmlTestResult] of xmlTestResults.entries()) {
						const xmlMessages = Array.from(
							xmlTestResult.querySelectorAll('svrl_text'),
						);
						let messages = xmlMessages.map(
							xmlMessage => xmlMessage.textContent,
						);
						messages.push('', 'At location:');
						messages = messages.concat(
							xmlTestResult.getAttribute('location').split('/*:'),
						);
						const testResult = {
							spec: module.name,
							section: part.name,
							test: `Error #${index + 1} ` + messages[0],
							messages,
							state: 'FAIL',
						};
						part.test ||= [];
						part.test.push(testResult);
					}
				});
			}
		}

		return new ValidationResult(result);
	}

	const instance = {
		validateContentByUrl,
		validateContentByText,
		validateContentByFile,
		convertInfoData,
		modules,
	};
	return instance;
})();
