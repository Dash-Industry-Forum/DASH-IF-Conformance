<?php

class XrefSpec
{
    public $data1;
    public $data2;
    public $error;

    function __construct($data1, $data2, $error)
    {
        $this->data1 = $data1;
        $this->data2 = $data2;
        $this->error = $error;
    }
};


class AC4_TOC
{
    public $bitstream_version;
    public $fs_index;
    public $frame_rate_index;
    public $b_single_presentation;
    public $short_program_id;
    public $presentation_version;
    public $n_presentations;
};


class DAC4
{
    public $bitstream_version;
    public $fs_index;
    public $frame_rate_index;
    public $b_single_presentation;
    public $short_program_id;
    public $presentation_version;
    public $n_presentations;
};


$ac4_toc_array = array();
$ac4_dac4_array  = array();

function InitDAC4($xml, &$ac4_dacs)
{
    $doms  = $xml->getElementsByTagName('ac4_dsi_v1');
    foreach ($doms as $dom)
    {
        $that = new DAC4();
        $that->fs_index = $dom->getAttribute('fs_index');
        $that->frame_rate_index = $dom->getAttribute('frame_rate_index');
        $that->bitstream_version = $dom->getAttribute('bitstream_version');
        $that->short_program_id = $dom->getAttribute('short_program_id');
        $that->n_presentations = $dom->getAttribute('n_presentations');
        $ac4_dacs[] = $that;
    }
}

function InitAC4Toc($xml, &$ac4_tocs)
{
    $doms = $xml->getElementsByTagName('ac4_toc');
    #printf ("\ndoms count %s\n", count($doms) );
    foreach ($doms as $dom)
    {

        $toc = new AC4_TOC();
        $toc->fs_index = $dom->getAttribute('fs_index');
        $toc->frame_rate_index = $dom->getAttribute('frame_rate_index');
        $toc->bitstream_version = $dom->getAttribute('bitstream_version');
        $toc->short_program_id = $dom->getAttribute('short_program_id');
        $toc->n_presentations = $dom->getAttribute('n_presentations');
        $ac4_tocs[] = $toc;
    }
    #printf ("\ntocs count %s\n", count($ac4_tocs) );
}

function CompareDoms($xml, $log, $d1, $d2, &$el)
{
    printf("\n%s\n", __FUNCTION__);
    $errs = 0;
    $d1_dom = $xml->getElementsByTagName($d1);
    $d2_dom = $xml->getElementsByTagName($d2);

    foreach ($el as $e)
    {
        $d1_e = $d1_dom->item(0)->getAttribute($e);
        $d2_e = $d2_dom->item(0)->getAttribute($e);

        printf("%-8s %-8s\n", $d1_e, $d2_e);
        if ($d1_e != $d2_e)
        {
            $errs++;
        }
    }

    printf ("errs %d\n", $errs);
    return $errs;
}

function CompareTocWithDac4_1($xml, $log, &$ac4_toc_array, &$ac4_dac4_array)
{
    InitAC4Toc($xml, $ac4_toc_array);
    InitDAC4($xml, $ac4_dac4_array);

    $ac4_dac4 = $ac4_dac4_array[0];

    $toc_index = 0;

    foreach ($ac4_toc_array as $toc)
    {
        if ($ac4_dac4->bitstream_version != $toc->bitstream_version)
        {
            fprintf($log,
                "###error- DOLBY <<- ETSI_TS_103_190-2_V1.2.1 E.6.3 [14197] ->>  toc[%d]->bitstream_version %d ac4_dac4->bitstream_version %d\n", $toc_index, $toc->bitstream_version, $ac4_dac4->bitstream_version );
        }

        if ($ac4_dac4->fs_index != $toc->fs_index)
        {
            fprintf($log, "###error- DOLBY <<- ETSI_TS_103_190-2_V1.2.1 E.6.4 [14203] ->> toc[%d]->fs_index %d ac4_dac4->fs_index %d\n", $toc_index, $toc->fs_index, $ac4_dac4->fs_index );
        }

        if ($ac4_dac4->frame_rate_index != $toc->frame_rate_index)
        {
            fprintf($log, "###error- DOLBY <<- ETSI_TS_103_190-2_V1.2.1 E.6.5 [14209] ->> toc[%d]->frame_rate_index %d ac4_dac4->frame_rate_index %d\n", $toc_index, $toc->frame_rate_index, $ac4_dac4->frame_rate_index );
        }

        if ($ac4_dac4->n_presentations != $toc->n_presentations)
        {
            fprintf($log, "###error- DOLBY <<- ETSI_TS_103_190-2_V1.2.1 E.6.6 [14215] ->> toc[%d]->n_presentations ac4_dac4->n_presentations %d\n", $toc_index, $toc->n_presentations, $ac4_dac4->n_presentations );
        }

        if ($ac4_dac4->short_program_id != $toc->short_program_id)
        {
            fprintf($log, "###error- DOLBY <<- ETSI_TS_103_190-2_V1.2.1 E.6.7 [14221] ->> toc[%d]->short_program_id ac4_dac4->short_program_id %d\n", $toc_index, $toc->short_program_id, $ac4_dac4->short_program_id );
        }
        $toc_index++;
    }
}


function CompareTocWithDac4($xml, $log, &$ac4_toc_array, &$ac4_dac4_array)
{
    InitAC4Toc($xml, $ac4_toc_array);
    InitDAC4($xml, $ac4_dac4_array);

    $ac4_dac4 = $ac4_dac4_array[0];

    $toc_index = 0;

    foreach ($ac4_toc_array as $toc)
    {
        if ($ac4_dac4->bitstream_version != $toc->bitstream_version)
        {
            fprintf($log,
                "###error- DOLBY <<- ETSI_TS_103_190-2_V1.2.1 E.6.3 [14197] ->>  toc[%d]->bitstream_version %d ac4_dac4->bitstream_version %d\n", $toc_index, $toc->bitstream_version, $ac4_dac4->bitstream_version );
        }

        if ($ac4_dac4->fs_index != $toc->fs_index)
        {
            fprintf($log, "###error- DOLBY <<- ETSI_TS_103_190-2_V1.2.1 E.6.4 [14203] ->> toc[%d]->fs_index %d ac4_dac4->fs_index %d\n", $toc_index, $toc->fs_index, $ac4_dac4->fs_index );
        }

        if ($ac4_dac4->frame_rate_index != $toc->frame_rate_index)
        {
            fprintf($log, "###error- DOLBY <<- ETSI_TS_103_190-2_V1.2.1 E.6.5 [14209] ->> toc[%d]->frame_rate_index %d ac4_dac4->frame_rate_index %d\n", $toc_index, $toc->frame_rate_index, $ac4_dac4->frame_rate_index );
        }

        if ($ac4_dac4->n_presentations != $toc->n_presentations)
        {
            fprintf($log, "###error- DOLBY <<- ETSI_TS_103_190-2_V1.2.1 E.6.6 [14215] ->> toc[%d]->n_presentations ac4_dac4->n_presentations %d\n", $toc_index, $toc->n_presentations, $ac4_dac4->n_presentations );
        }

        if ($ac4_dac4->short_program_id != $toc->short_program_id)
        {
            fprintf($log, "###error- DOLBY <<- ETSI_TS_103_190-2_V1.2.1 E.6.7 [14221] ->> toc[%d]->short_program_id ac4_dac4->short_program_id %d\n", $toc_index, $toc->short_program_id, $ac4_dac4->short_program_id );
        }
        $toc_index++;
    }
}


function SingleTest( $xml_file, $item1, $item2, $msg )
{
    $xml = get_DOM($xml_file, 'atomlist');
    $dom  = $xml->getElementsByTagName('ac4_toc');
    #printf("count %d\n",count($dom));
    #printf("%s\n", print_r($dom->item(0), true) );
    #$ab = simplexml_load_file($xml_file);
    #printf("%s\n", print_r($ab, true) );

    #$result = $ab->xpath('atomlist/moov/trak/mdia');
    #printf("%s\n", print_r($result->item(0), true) );
    #$xml_doc = new DOMDocument();
    #$xml_doc->load($xml_file);
    #$xml_dom = $xml_doc->getElementsByTagName('atomlist')->item(0);
    #printf("%s\n", print_r($xml_dom, true) );
    #$result = $xml_doc->xpath('dac4');
    #printf("%s\n", print_r($result, true) );
    return true;
}


function ValidateDolby($adaptation_set, $representation)
{
    global $session_dir, $current_period, $reprsentation_error_log_template;
    global $current_adaptation_set, $current_representation;
    global $ac4_toc, $ac4_dac4;

    $trackErrorFileName =  $session_dir . '/Period' . $current_period .
                            '/' . str_replace(
                                    array('$AS$', '$R$'),
                                    array($current_adaptation_set, $current_representation),
                           $reprsentation_error_log_template)  .  '.txt';


    $trackErrorFile = open_file( $trackErrorFileName, 'a+');

    if (!$trackErrorFile)
    {
        error_log(" "  .  __FUNCTION__ . " " . __LINE__ . ":" .  " " . " File not found \n");
        return;
    }

    if ($representation['mimeType'] == 'audio/mp4' )
    {
        $AtomXMLFileName=
            $session_dir . '/Period' . $current_period . '/' .
            str_replace(array('$AS$', '$R$'),array($current_adaptation_set), 'Adapt$AS$' )  . '/' .
            str_replace(array('$AS$', '$R$'),array($current_adaptation_set, $current_representation), 'Adapt$AS$rep$R$' )  . '.xml';

        $xml = get_DOM($AtomXMLFileName, 'atomlist');
        CompareTocWithDac4($xml, $trackErrorFile, $ac4_toc_array, $ac4_dac4_array);
    }
    fclose($trackErrorFile);
}


