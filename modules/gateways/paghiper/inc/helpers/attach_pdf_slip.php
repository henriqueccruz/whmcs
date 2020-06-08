<?php
/**
 * Adiciona boleto bancário como página adicional na fatura anexa no WHMCS
 * @author     Henrique Cruz | henriquecruz.com.br
 * @copyright  Copyright (c) 2019 https://henriquecruz.com.br
 */

use setasign\Fpdi;

$whmcs_url = rtrim(\App::getSystemUrl(),"/");
$json_url = $whmcs_url."/modules/gateways/paghiper.php?invoiceid=".$invoiceid."&uuid=".$clientsdetails['userid']."&mail=".$clientsdetails['email']."&json=1";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $json_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$json = curl_exec($ch);
$result = json_decode($json);

$transaction_id = (isset($result->transaction_id)) ? $result->transaction_id : '';
$pdf_url = (isset($result->bank_slip)) ? $result->bank_slip->url_slip_pdf : $result->url_slip_pdf;

if ((in_array($status, array('Unpaid', 'Payment Pending'))) && (isset($pdf_url) && !empty($pdf_url)) && (isset($transaction_id) && !empty($transaction_id))){

    $basedir = dirname(__FILE__);
    $print_billet = FALSE;

    // Primeiro checamos se temos um boleto para disponibilizar
    if(file_exists($filename)) {
        $print_billet = TRUE;
    } else {
        if(is_writable($filename)) {
            $fp = fopen($filename, 'w');
            fwrite($fp, $rawdata);
            fclose($fp);
            
            if(file_exists($filename)) {
                $print_billet = TRUE;
            }
        }
    }

    if($print_billet) {

        /* Bloco inicializador do boleto */
        require_once($basedir.'/inc/fpdi/autoload.php');
        require_once($basedir.'/inc/fpdi/TcpdfFpdi.php');
        $pdf = new Fpdi\TcpdfFpdi();
    
        // TODO: Implementar header e footer aqui
        $pdf->SetPrintHeader(false);
        $pdf->SetPrintFooter(false);
    
        $pdf->AddPage();
    
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $pdf_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
        $rawdata = curl_exec($ch);
    
        $filename = $basedir.'/tmp/'.$transaction_id.'.pdf';
    
        $pdf->setSourceFile($filename);
        $tplIdx = $pdf->importPage(1);
    
        $pdf->useTemplate($tplIdx, 0, 0, 210);
    
    
        /* Bloco inicializador do template comum */
        $pdf->SetPrintHeader(false);
        $pdf->SetPrintFooter(false);
        $pdf->AddPage();

    }

} ?>