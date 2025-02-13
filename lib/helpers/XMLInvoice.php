<?php

/**
 * This file is part of the Kreatif\Project package.
 *
 * @author Kreatif GmbH
 * @author a.platter@kreatif.it
 * Date: 26.03.19
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 *
 * Reference:
 * https://www.fatturapa.gov.it/it/norme-e-regole/documentazione-fattura-elettronica/formato-fatturapa/
 * https://www.fatturapa.gov.it/export/fatturazione/sdi/fatturapa/v1.2.1/Rappresentazione_tabellare_del_tracciato_FatturaPA_versione_1.2.1.pdf
 *
 * Test-Tool:
 * https://fatturazione-elettronica-pa.assocons.it/validazione-fattura-elettronica.html
 */

namespace FriendsOfREDAXO\Simpleshop;

class XMLInvoice
{
    protected static $inst = null;

    protected $data;
    protected $xml;

    public static function factory($new = false)
    {
        if ($new || self::$inst === null) {
            self::$inst = new self();
        } else {
            return self::$inst;
        }


        self::$inst->data["transmitter_country_code"] = "IT"; //COUNTRY TRANSMITTER (COUNTRY SOFTWARE HOUSE WHICH SENDS XML)
        self::$inst->data["transmitter_vat_number"]   = "###company.vat###"; ///VAT_ID TRANSMITTER (VAT_ID SOFTWARE HOUSE WHICH SENDS XML)

        // THIRD PARTY
        self::$inst->data["third_party_country_code"] = "";
        self::$inst->data["third_party_vat_number"]   = "";
        self::$inst->data["third_party_name"]         = "";
        self::$inst->data["third_party_code"]         = ""; //[CC]: cessionario / committente [TZ]: terzo.


        /*TODO*/
        self::$inst->data["transmitter_progressiv_code"] = "123"; //INTERNAL INVOICE NUMBER OF SOFTWARE HOUSE
        self::$inst->data["transmitter_format"]          = "FPR12"; //[FPA12] = fattura verso PA  [FPR12] = fattura verso privati
        self::$inst->data["transmitter_code_receiver"]   = "0000000"; //CodiceDestinatario

        // CLIENT DATA
        self::$inst->data["company_country_code"]       = "IT";
        self::$inst->data["company_vat_number"]         = "###company.vat###"; //VAT ID
        self::$inst->data["company_private_vat_number"] = "###company.vat###"; //PRIVATE VAT_ID (COD.FISCALE)
        self::$inst->data["company_name"]               = "###company.name##";
        self::$inst->data["company_fiscal_code"]        = "RF01"; //FISCAL CODE - LOOK AT CODE TABLE

        $street = explode(' ', \Wildcard::get('company.street'));
        $number = array_pop($street);

        self::$inst->data["company_head_quarter_street"]    = implode(' ', $street);
        self::$inst->data["company_head_quarter_street_no"] = $number;
        self::$inst->data["company_head_quarter_zip"]       = "###company.postal###";
        self::$inst->data["company_head_quarter_city"]      = "###company.location###";
        self::$inst->data["company_head_quarter_province"]  = "###company.province###";
        self::$inst->data["company_head_quarter_nation"]    = "IT";

        self::$inst->data["company_rea_office"]             = "BZ";
        self::$inst->data["company_rea_no"]                 = "###company.rea_no###";
        self::$inst->data["company_rea_capital"]            = 0;
        self::$inst->data["company_rea_single_shareholder"] = "SM"; //[SU] : socio unico [SM] : più soci
        self::$inst->data["company_rea_liquidation"]        = "LN"; //[LS] : in liquidazione [LN] : non in liquidazione

        // CUSTOMER DATA
        self::$inst->data["receiver_country_code"]       = ""; // IT
        self::$inst->data["receiver_vat_number"]         = "";
        self::$inst->data["receiver_private_vat_number"] = "";
        self::$inst->data["receiver_name"]               = ""; // Customer name
        self::$inst->data["receiver_fiscal_code"]        = ""; //FISCAL CODE - LOOK AT CODE TABLE

        self::$inst->data["receiver_head_quarter_street"]    = "";
        self::$inst->data["receiver_head_quarter_street_no"] = " ";
        self::$inst->data["receiver_head_quarter_zip"]       = "";
        self::$inst->data["receiver_head_quarter_city"]      = "";
        self::$inst->data["receiver_head_quarter_province"]  = "";
        self::$inst->data["receiver_head_quarter_nation"]    = ""; // IT

        // DOCUMENT
        self::$inst->data["document_type"]     = "TD01"; //LOOK AT CODE TABLE | TD01 = fattura, TD04 = nota di credito
        self::$inst->data["document_currency"] = "EUR";
        self::$inst->data["document_date"]     = "";
        self::$inst->data["document_number"]   = "";
        self::$inst->data["document_vat_type"] = "I"; // [I]: IVA ad esigibilità immediata, [D]: IVA ad esigibilità differita, [S]: scissione dei pagamenti

        self::$inst->data["document_lines"][1]["line_number"]         = 1;
        self::$inst->data["document_lines"][1]["line_description"]    = "";
        self::$inst->data["document_lines"][1]["line_quantity"]       = 1;
        self::$inst->data["document_lines"][1]["line_single_price"]   = 0;
        self::$inst->data["document_lines"][1]["line_total_price"]    = 0;
        self::$inst->data["document_lines"][1]["line_vat_percentage"] = 22;

        // SALES
        self::$inst->data["sales_totale_vat_percentage"] = 22;
        self::$inst->data["sales_totale_netto"]          = 0;
        self::$inst->data["sales_totale_vat"]            = 0;
        self::$inst->data["sales_totale"]                = 0;

        // PAYMENT
        self::$inst->data["payment_conditions"] = "";
        self::$inst->data["payment_method"]     = "";
        self::$inst->data["payment_due_date"]   = "";
        self::$inst->data["paypment_cc_iban"]   = "";
        self::$inst->data["paypment_cc_abi"]    = "";
        self::$inst->data["paypment_cc_cab"]    = "";

        // ATTACHMENT
        self::$inst->data["attachment_format"] = "pdf";
        self::$inst->data["attachment_path"]   = "";

        return self::$inst;
    }

    public function getData()
    {
        return $this->data;
    }

    public function setData($data)
    {
        $this->data = $data;
    }

    public function setValue($key, $value)
    {
        $this->data[$key] = $value;
    }

    public function buildXML()
    {
        /* BUILD XML */
        $this->xml                = new \SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><FatturaElettronica xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"   xmlns:xsd="http://www.w3.org/2001/XMLSchema" versione="FPR12"  xmlns="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2" />');
        $FatturaElettronicaHeader = $this->xml->addChild("FatturaElettronicaHeader");
        $FatturaElettronicaHeader->addAttribute('xmlns', "");
        $DatiTrasmissione = $FatturaElettronicaHeader->addChild("DatiTrasmissione");
        $IdTrasmittente   = $DatiTrasmissione->addChild("IdTrasmittente");
        $IdTrasmittente->addChild("IdPaese", $this->data["transmitter_country_code"]);
        $IdTrasmittente->addChild("IdCodice", $this->data["transmitter_vat_number"]);

        $DatiTrasmissione->addChild("ProgressivoInvio",
            is_int($this->data["transmitter_progressiv_code"]) ? str_pad($this->data["transmitter_progressiv_code"], 10, '0',
                STR_PAD_LEFT) : $this->data["transmitter_progressiv_code"]);
        $this->dataTrasmissioneValue = $this->data["transmitter_format"] != "" ? $this->data["transmitter_format"] : ($this->data["company_vat_number"] != "" ? "FPA12" : "FPR12");
        $DatiTrasmissione->addChild("FormatoTrasmissione", $this->dataTrasmissioneValue);
        $codiceDestinatarioValue = $this->data["transmitter_code_receiver"];
        if ($codiceDestinatarioValue == "") {
            switch ($this->dataTrasmissioneValue) {
                case "FPA12":
                    $codiceDestinatarioValue = "000000";
                    break;
                case "FPR12":
                    $codiceDestinatarioValue = ($this->data["receiver_head_quarter_nation"] == "IT" ? "0000000" : "XXXXXXX");
                    break;
            }
        }
        $DatiTrasmissione->addChild("CodiceDestinatario", $codiceDestinatarioValue);

        $CedentePrestatore     = $FatturaElettronicaHeader->addChild("CedentePrestatore");
        $CedenteDatiAnagrafici = $CedentePrestatore->addChild("DatiAnagrafici");
        $CedenteIdFiscaleIVA   = $CedenteDatiAnagrafici->addChild("IdFiscaleIVA");
        $CedenteIdFiscaleIVA->addChild("IdPaese", $this->data["company_country_code"]);
        $CedenteIdFiscaleIVA->addChild("IdCodice", $this->data["company_vat_number"]);
        $CedenteDatiAnagrafici->addChild("CodiceFiscale", $this->data["company_private_vat_number"]);
        $CedenteAnagrafica = $CedenteDatiAnagrafici->addChild("Anagrafica");
        $CedenteAnagrafica->addChild("Denominazione", htmlspecialchars($this->data["company_name"], ENT_QUOTES, "utf-8"));
        $CedenteDatiAnagrafici->addChild("RegimeFiscale", $this->data["company_fiscal_code"]);

        $CedenteSede = $CedentePrestatore->addChild("Sede");
        $CedenteSede->addChild("Indirizzo", $this->data["company_head_quarter_street"]);
        $CedenteSede->addChild("NumeroCivico", $this->data["company_head_quarter_street_no"]);
        $CedenteSede->addChild("CAP", $this->data["company_head_quarter_zip"]);
        $CedenteSede->addChild("Comune", $this->data["company_head_quarter_city"]);
        $CedenteSede->addChild("Provincia", $this->data["company_head_quarter_province"]);
        $CedenteSede->addChild("Nazione", $this->data["company_head_quarter_nation"]);

        //        $IscrizioneREA = $CedentePrestatore->addChild("IscrizioneREA");
        //        $IscrizioneREA->addChild("Ufficio", $this->data["company_rea_office"]);
        //        $IscrizioneREA->addChild("NumeroREA", $this->data["company_rea_no"]);
        //        $IscrizioneREA->addChild("CapitaleSociale", number_format($this->data["company_rea_capital"], 2, ".", ""));
        //        $IscrizioneREA->addChild("SocioUnico", $this->data["company_rea_single_shareholder"]);
        //        $IscrizioneREA->addChild("StatoLiquidazione", $this->data["company_rea_liquidation"]);

        $CessionarioCommittente    = $FatturaElettronicaHeader->addChild("CessionarioCommittente");
        $CessionarioDatiAnagrafici = $CessionarioCommittente->addChild("DatiAnagrafici");

        if ($this->data["receiver_vat_number"]) {
            $CessionarioIdFiscaleIVA = $CessionarioDatiAnagrafici->addChild("IdFiscaleIVA");
            $CessionarioIdFiscaleIVA->addChild("IdPaese", $this->data["receiver_country_code"]);
            $CessionarioIdFiscaleIVA->addChild("IdCodice", $this->data["receiver_vat_number"]);
        }
        $CessionarioDatiAnagrafici->addChild("CodiceFiscale", mb_strtoupper($this->data["receiver_private_vat_number"]));
        $CessionarioAnagrafica = $CessionarioDatiAnagrafici->addChild("Anagrafica");
        $CessionarioAnagrafica->addChild("Denominazione", htmlspecialchars($this->data["receiver_name"], ENT_QUOTES, "utf-8"));
        //                    $CessionarioDatiAnagrafici->addChild("RegimeFiscale",$this->data["receiver_fiscal_code"]);

        $CessionarioSede = $CessionarioCommittente->addChild("Sede");
        $CessionarioSede->addChild("Indirizzo", htmlspecialchars($this->data["receiver_head_quarter_street"], ENT_QUOTES, "utf-8"));
        $CessionarioSede->addChild("NumeroCivico", $this->data["receiver_head_quarter_street_no"]);
        $CessionarioSede->addChild("CAP", $this->data["receiver_head_quarter_zip"]);
        $CessionarioSede->addChild("Comune", $this->data["receiver_head_quarter_city"]);
        $CessionarioSede->addChild("Provincia", $this->data["receiver_head_quarter_province"]);
        $CessionarioSede->addChild("Nazione", $this->data["receiver_head_quarter_nation"]);

        if ($this->data["third_party_name"]) {
            $TerzoIntermediarioOSoggettoEmittente = $FatturaElettronicaHeader->addChild("TerzoIntermediarioOSoggettoEmittente");
            $TerzoIntermediarioDatiAnagrafici     = $TerzoIntermediarioOSoggettoEmittente->addChild("DatiAnagrafici");
            $TerzoIntermediarioIdFiscaleIVA       = $TerzoIntermediarioDatiAnagrafici->addChild("IdFiscaleIVA");
            $TerzoIntermediarioIdFiscaleIVA->addChild("IdPaese", $this->data["third_party_country_code"]);
            $TerzoIntermediarioIdFiscaleIVA->addChild("IdCodice", $this->data["third_party_vat_number"]);
            //        $TerzoIntermediarioDatiAnagrafici->addChild("CodiceFiscale", $this->data["receiver_private_vat_number"]);
            $TerzoIntermediarioAnagrafica = $TerzoIntermediarioDatiAnagrafici->addChild("Anagrafica");
            $TerzoIntermediarioAnagrafica->addChild("Denominazione", $this->data["third_party_name"]);

            $FatturaElettronicaHeader->addChild("SoggettoEmittente", $this->data["third_party_code"]);
        }


        $FatturaElettronicaBody = $this->xml->addChild("FatturaElettronicaBody");
        $FatturaElettronicaBody->addAttribute('xmlns', "");
        $DatiGenerali          = $FatturaElettronicaBody->addChild("DatiGenerali");
        $DatiGeneraliDocumento = $DatiGenerali->addChild("DatiGeneraliDocumento");
        $DatiGeneraliDocumento->addChild("TipoDocumento", $this->data["document_type"]);
        $DatiGeneraliDocumento->addChild("Divisa", $this->data["document_currency"]);
        $DatiGeneraliDocumento->addChild("Data", $this->data["document_date"]);
        $DatiGeneraliDocumento->addChild("Numero", $this->data["document_number"]);
        $DatiGeneraliDocumento->addChild("ImportoTotaleDocumento", number_format($this->data["sales_totale"], 2, '.', ''));

        $DatiBeniServizi = $FatturaElettronicaBody->addChild("DatiBeniServizi");
        foreach ($this->data["document_lines"] as $key => $line) {
            $DettaglioLinee = $DatiBeniServizi->addChild("DettaglioLinee");
            $DettaglioLinee->addChild("NumeroLinea", $line["line_number"]);
            $DettaglioLinee->addChild("Descrizione", htmlspecialchars($line["line_description"], ENT_QUOTES, "utf-8"));
            $DettaglioLinee->addChild("Quantita", number_format($line["line_quantity"], 8, ".", ""));
            $DettaglioLinee->addChild("PrezzoUnitario", number_format($line["line_single_price"], 2, ".", ""));
            $DettaglioLinee->addChild("PrezzoTotale", number_format($line["line_total_price"], 2, ".", ""));
            $DettaglioLinee->addChild("AliquotaIVA", number_format($line["line_vat_percentage"], 2, ".", ""));
        }

        $DatiRiepilogo = $DatiBeniServizi->addChild("DatiRiepilogo");
        $DatiRiepilogo->addChild("AliquotaIVA", number_format($this->data["sales_totale_vat_percentage"], 2, ".", ""));
        $DatiRiepilogo->addChild("ImponibileImporto", number_format($this->data["sales_totale_netto"], 2, ".", ""));
        $DatiRiepilogo->addChild("Imposta", number_format($this->data["sales_totale_vat"], 2, ".", ""));
        $DatiRiepilogo->addChild("EsigibilitaIVA", $this->data["document_vat_type"]);

        if (!empty($this->data["payment_conditions"])) {
            $DatiPagamento = $FatturaElettronicaBody->addChild("DatiPagamento");
            $DatiPagamento->addChild("CondizioniPagamento", $this->data["payment_conditions"]);
            $DettaglioPagamento = $DatiPagamento->addChild("DettaglioPagamento");
            $DettaglioPagamento->addChild("ModalitaPagamento", $this->data["payment_method"]);
            $DettaglioPagamento->addChild("DataScadenzaPagamento", $this->data["payment_due_date"]);
            $DettaglioPagamento->addChild("ImportoPagamento", number_format($this->data["sales_totale"], 2, '.', ''));

            // let a string only be alphanumeric
            $institute = preg_replace('/[^A-Za-z0-9\s]/', '', strip_tags(strtr($this->data["paypment_institute"], [
                "\n"     => ' ',
                "\r"     => ' ',
                '<br/>'  => ' ',
                '<br>'   => ' ',
                '<br />' => ' ',
            ])));
            $DettaglioPagamento->addChild("IstitutoFinanziario", substr($institute, 0, 80));
            $DettaglioPagamento->addChild("IBAN", $this->data["paypment_cc_iban"]);
            if (!empty($this->data["paypment_cc_abi"])) {
                $DettaglioPagamento->addChild("ABI", $this->data["paypment_cc_abi"]);
            }
            if (!empty($this->data["paypment_cc_cab"])) {
                $DettaglioPagamento->addChild("CAB", $this->data["paypment_cc_cab"]);
            }
        }

        if ($this->data["attachment_path"]) {
            $Allegati = $FatturaElettronicaBody->addChild("Allegati");
            $Allegati->addChild("NomeAttachment", $this->data["attachment_name"]);
            $Allegati->addChild("FormatoAttachment", $this->data["attachment_format"]);
            $Allegati->addChild("Attachment", base64_encode(file_get_contents($this->data["attachment_path"])));
        }
    }

    public function getXML()
    {
        return $this->xml;
    }

    public function getXMLFormated()
    {
        $dom = dom_import_simplexml($this->xml)->ownerDocument;

        $dom->formatOutput = true;
        return $dom->saveXML();
    }
}