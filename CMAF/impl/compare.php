<?php

global $infofile_template;

$str = createString(); //load the comparison result xml structure
$compareResultXml = simplexml_load_string($str);
$compareResultXml->addAttribute('comparedIds', "[rep=" . $id1 . " rep=" . $id2 . "]");

$storedAttributeValue1;
$storedAttributeValue2;
foreach ($this->boxList as $boxListKey => $boxListValue) {
    $xmlNode1 = $xml1->getElementsByTagName($boxListKey);
    $xmlNode2 = $xml2->getElementsByTagName($boxListKey);

    if ($xmlNode1->length == $xmlNode2->length) {
        if ($boxListKey == 'tkhd') {
            if (!$xmlNode1->length) {
                $this->careAboutElst = true;
            }
        }

        foreach ($xmlNode1 as $i => $xmlChild1) {
            $xmlChild2 = $xmlNode2->item($i);

            foreach ($boxListValue as $attribute) {
                $xmlAttribute1 = $xmlChild1->getAttribute($attribute);
                $xmlAttribute2 = $xmlChild2->getAttribute($attribute);

                if ($boxListKey == 'mdhd' && $attribute == 'timescale') {
                    $storedAttributeValue1 = $xmlAttribute1;
                    $storedAttributeValue2 = $xmlAttribute2;
                }
                if ($boxListKey == "hdlr" && $attribute == "handler_type") {
                    if ($xmlAttribute1 == "soun" && $xmlAttribute2 == "soun") {
                        if (
                            doubleval($storedAttributeValue1) % 2 != 0 ||
                            doubleval($storedAttributeValue2) % 2 != 0
                        ) {
                            $this->careAboutMdhd = true;
                        }
                    } else {
                        $this->careAboutMdhd = true;
                    }
                }

                //For comparing file brands with media profile brands
                if ($boxListKey == 'ftyp' && $attribute == 'compatible_brands') {
                    $this->validateFileBrands($xmlAttribute1, $xmlAttribute2);
                }

                // Check for 'sinf' box
                $sinfBox = $boxListKey;
                $sinfBoxAttribute = $attribute;
                if ($boxListKey == 'frma' || $boxListKey == 'schm' || $boxListKey == 'schi') {
                    $sinfBox = 'sinf';
                    $sinfBoxAttribute = $boxListKey;
                }

                $string = ($xmlAttribute1 == $xmlAttribute2) ? 'Yes' : 'No';
                if (isset($compareResultXml->$sinfBox->attributes()[$sinfBoxAttribute])) {
                    $compareResultXml->$sinfBox->attributes()->$sinfBoxAttribute =
                            ((string) $compareResultXml->$sinfBox->attributes()->$sinfBoxAttribute) . ' ' . $string;
                } else {
                    $compareResultXml->$sinfBox->addAttribute($sinfBoxAttribute, $string);
                }
            }
        }
    } else {
        if (isset($compareResultXml->$boxListKey->attributes()[$attribute])) {
            $compareResultXml->$boxListKey->attributes()->$attribute =
                    ((string) $compareResultXml->$boxListKey->attributes()->$attribute) . ' No';
        } else {
            $compareResultXml->$boxListKey->addAttribute($attribute, 'No');
        }
    }
}

$compareResultXml->asXml($path); //save changes
