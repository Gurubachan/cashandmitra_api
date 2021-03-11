<?php

function curl( $url, $method,$postData, $authToken=null){
    try {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS =>$postData,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization:'.$authToken,
            ),
        ));

        $response = curl_exec($curl);
        $responseInfo = curl_getinfo($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        if($httpCode === 200){
            return ['response'=>true,'message'=>$responseInfo,'data'=>json_decode($response)];
        }else{
            return ['response'=>false,'response_code'=>$httpCode,'message'=>$responseInfo,'data'=>json_decode($response)];
        }

    }catch (Exception $exception){
        return ['response'=>false,'message'=>$exception->getMessage()];
    }

}
