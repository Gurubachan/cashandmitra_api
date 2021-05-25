<?php

function curl( $url, $method,$postData, $header=array()){
    try {
        $curl = curl_init();
        $primaryHeader=array('Content-Type: application/json');
        if(!empty($header)){
            $primaryHeader= array_merge($primaryHeader,$header);
        }

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
           /* CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization:'.$authToken,
                $other
            ),*/
            CURLOPT_HTTPHEADER=>$primaryHeader
        ));

        $response = curl_exec($curl);
        $responseInfo = curl_getinfo($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        logger($response);
        if($httpCode === 200){
            return ['response'=>true,'message'=>'Success','data'=>json_decode($response)];
        }else{
            logger($response);
            return ['response'=>false,'response_code'=>$httpCode,'message'=>'Failure','data'=>json_decode($response)];
        }

    }catch (Exception $exception){
        logger($exception);
        return ['response'=>false,'message'=>$exception->getMessage()];
    }

}
