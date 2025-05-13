<?php

namespace App\Services;

require_once app_path().'/Helper/Oauth256.php';

use OAuthConsumer;
use OAuthRequest;
use OAuthSignatureMethod_HMAC_SHA256;
use OAuthToken;

class Netsuite {

    public static function get($script, $id, $start, $end, $type = '', $type_ambiente = null)
    {
        $ambiente = ($type_ambiente == null) ? config('app.ambiente') : $type_ambiente;
        if($ambiente == 'local'){
            $NETSUITE_DEPLOY_ID = '1';
            $NETSUITE_AMBIENTE = '4572765-sb1';
            $NETSUITE_ACCOUNT = '4572765_SB1';
            $NETSUITE_CONSUMER_KEY = config('services.netsuiteSandbox.CONSUMER_KEY');
            $NETSUITE_CONSUMER_SECRET = config('services.netsuiteSandbox.CONSUMER_SECRET');
            $NETSUITE_TOKEN_ID = config('services.netsuiteSandbox.TOKEN_ID');
            $NETSUITE_TOKEN_SECRET = config('services.netsuiteSandbox.TOKEN_SECRET');
        } else {
            $NETSUITE_DEPLOY_ID ='1';
            $NETSUITE_AMBIENTE ='4572765';
            $NETSUITE_ACCOUNT ='4572765';
            $NETSUITE_CONSUMER_KEY = config('services.netsuiteProduction.CONSUMER_KEY');
            $NETSUITE_CONSUMER_SECRET = config('services.netsuiteProduction.CONSUMER_SECRET');
            $NETSUITE_TOKEN_ID = config('services.netsuiteProduction.TOKEN_ID');
            $NETSUITE_TOKEN_SECRET = config('services.netsuiteProduction.TOKEN_SECRET');
        }

        $NETSUITE_SCRIPT_ID = $script;
        if($id == null){
            $url = "https://$NETSUITE_AMBIENTE.restlets.api.netsuite.com/app/site/hosting/restlet.nl?script=$NETSUITE_SCRIPT_ID&deploy=$NETSUITE_DEPLOY_ID&start=$start&end=$end";
        } else {
            $url = "https://$NETSUITE_AMBIENTE.restlets.api.netsuite.com/app/site/hosting/restlet.nl?script=$NETSUITE_SCRIPT_ID&deploy=$NETSUITE_DEPLOY_ID&idbusqueda=$id&type=$type&start=$start&end=$end";
        }

        $consumer = new OAuthConsumer($NETSUITE_CONSUMER_KEY, $NETSUITE_CONSUMER_SECRET);
        $token = new OAuthToken($NETSUITE_TOKEN_ID, $NETSUITE_TOKEN_SECRET);
        $sig = new OAuthSignatureMethod_HMAC_SHA256(); //Signature

        $params = array(
            'oauth_nonce' => md5(mt_rand()),
            'oauth_timestamp' => idate('U'),
            'oauth_version' => '1.0',
            'oauth_token' => $NETSUITE_TOKEN_ID,
            'oauth_consumer_key' => $NETSUITE_CONSUMER_KEY,
            'oauth_signature_method' => $sig->get_name(),
        );

        $req = new OAuthRequest('GET', $url, $params);
        $req->set_parameter('oauth_signature', $req->build_signature($sig, $consumer, $token));
        $req->set_parameter('realm', $NETSUITE_ACCOUNT);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            $req->to_header().',realm="'.$NETSUITE_ACCOUNT.'',
            'Content-Type: application/json',
            'Accept-Language: en',
            'Accept-Language: es'
        ]);

        $respuesta = curl_exec($ch);
        curl_close($ch);

        return $respuesta;
    }

    public static function post($script, $json, $type_ambiente = null)
    {
        $ambiente = ($type_ambiente == null) ? config('app.ambiente') : $type_ambiente;
        if($ambiente == 'local'){
            $NETSUITE_URL = "https://4572765-sb1.restlets.api.netsuite.com/app/site/hosting/restlet.nl";
            $NETSUITE_SCRIPT_ID = $script;
            $NETSUITE_DEPLOY_ID = '1';
            $NETSUITE_ACCOUNT = '4572765_SB1';
            $NETSUITE_CONSUMER_KEY = config('services.netsuiteSandbox.CONSUMER_KEY');
            $NETSUITE_CONSUMER_SECRET = config('services.netsuiteSandbox.CONSUMER_SECRET');
            $NETSUITE_TOKEN_ID = config('services.netsuiteSandbox.TOKEN_ID');
            $NETSUITE_TOKEN_SECRET = config('services.netsuiteSandbox.TOKEN_SECRET');
        } else {
            $NETSUITE_URL = "https://4572765.restlets.api.netsuite.com/app/site/hosting/restlet.nl";
            $NETSUITE_SCRIPT_ID = $script;
            $NETSUITE_DEPLOY_ID ='1';
            $NETSUITE_ACCOUNT ='4572765';
            $NETSUITE_CONSUMER_KEY = config('services.netsuiteProduction.CONSUMER_KEY');
            $NETSUITE_CONSUMER_SECRET = config('services.netsuiteProduction.CONSUMER_SECRET');
            $NETSUITE_TOKEN_ID = config('services.netsuiteProduction.TOKEN_ID');
            $NETSUITE_TOKEN_SECRET = config('services.netsuiteProduction.TOKEN_SECRET');
        }

        $data_string = $json;

        $oauth_nonce = md5(mt_rand());
        $oauth_timestamp = time();
        $oauth_signature_method = 'HMAC-SHA256';
        $oauth_version = "1.0";

        $base_string =
            "POST&" . urlencode($NETSUITE_URL) . "&" .
            urlencode(
                "deploy=" . $NETSUITE_DEPLOY_ID
              . "&oauth_consumer_key=" . $NETSUITE_CONSUMER_KEY
              . "&oauth_nonce=" . $oauth_nonce
              . "&oauth_signature_method=" . $oauth_signature_method
              . "&oauth_timestamp=" . $oauth_timestamp
              . "&oauth_token=" . $NETSUITE_TOKEN_ID
              . "&oauth_version=" . $oauth_version
              . "&realm=" . $NETSUITE_ACCOUNT
              . "&script=" . $NETSUITE_SCRIPT_ID
            );
        $sig_string = urlencode($NETSUITE_CONSUMER_SECRET) . '&' . urlencode($NETSUITE_TOKEN_SECRET);
        $signature = base64_encode(hash_hmac("sha256", $base_string, $sig_string, true));

        $auth_header = "OAuth "
            . 'oauth_signature="' . rawurlencode($signature) . '", '
            . 'oauth_version="' . rawurlencode($oauth_version) . '", '
            . 'oauth_nonce="' . rawurlencode($oauth_nonce) . '", '
            . 'oauth_signature_method="' . rawurlencode($oauth_signature_method) . '", '
            . 'oauth_consumer_key="' . rawurlencode($NETSUITE_CONSUMER_KEY) . '", '
            . 'oauth_token="' . rawurlencode($NETSUITE_TOKEN_ID) . '", '
            . 'oauth_timestamp="' . rawurlencode($oauth_timestamp) . '", '
            . 'realm="' . rawurlencode($NETSUITE_ACCOUNT) .'"';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $NETSUITE_URL . '?&script=' . $NETSUITE_SCRIPT_ID . '&deploy=' . $NETSUITE_DEPLOY_ID . '&realm=' . $NETSUITE_ACCOUNT);
        curl_setopt($ch, CURLOPT_POST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: ' . $auth_header,
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data_string)
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

}
