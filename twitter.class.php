<?php

/**
 * This class allows one to programmatically update Twitter status using OAuth.
 * 
 * Further documentation available on GitHub Wiki page: https://github.com/kiprobinson/TwitterStatusUpdate/wiki
 * 
 * @author Kip Robinson, https://github.com/kiprobinson
 */
class twitter
{
  //FILL IN THESE VALUES!!
  private $consumerKey      = '???';
  private $consumerSecret   = '???';
  private $oauthToken       = '???';
  private $oauthTokenSecret = '???';
  
  function __construct($consumerKey='', $consumerSecret='', $oauthToken='', $oauthTokenSecret='')
  {
    if($consumerKey)
      $this->consumerKey = $consumerKey;
    if($consumerSecret)
      $this->consumerSecret = $consumerSecret;
    if($oauthToken)
      $this->oauthToken = $oauthToken;
    if($oauthTokenSecret)
      $this->oauthTokenSecret = $oauthTokenSecret;
  }
  
  /**
   * Posts status to a twitter account. Returns true if successful, result
   * of curl_getinfo() if failure. 
   */ 
  function postStatus($status)
  {
    return $this->apiCall('https://api.twitter.com/1.1/statuses/update.json', array('status'=>$status));
  }
  
  private function apiCall($url, $params)
  {
    $method = 'POST';
    
    //postString covers what will *actually* be posted
    $postString = $this->joinParams($params);
    
    //now adding to $params other OAuth properties...
    $params['oauth_nonce']            = sha1(time() . mt_rand());
    $params['oauth_timestamp']        = time();
    $params['oauth_signature_method'] = 'HMAC-SHA1';
    $params['oauth_version']          = '1.0';
    $params['oauth_consumer_key']     = $this->consumerKey;
    $params['oauth_token']            = $this->oauthToken;
    
    ksort($params); //IMPORTANT!
    $paramString = $this->joinParams($params);
    
    $signatureBaseString = $method . '&' . rawurlencode($url) . '&' . rawurlencode($paramString);
    $signatureKey = $this->consumerSecret . '&' . $this->oauthTokenSecret;
    $params['oauth_signature'] = base64_encode(hash_hmac('sha1', $signatureBaseString, $signatureKey, true));
    
    $authHeader = 'Authorization: OAuth realm=""';
    foreach($params as $key => $val)
      $authHeader .= ", $key=\"" . rawurlencode($val) . "\"";
    
    
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 2);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $postString);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); //required for HTTPS URL
    curl_setopt($curl, CURLOPT_HTTPHEADER, array($authHeader));
    
    $content = curl_exec($curl);
    $resultInfo = curl_getinfo($curl);
    curl_close($curl);
    
    if ($resultInfo['http_code'] == 200)
      return true;
    
    $resultInfo['content'] = $content;
    return $resultInfo;
  }
  
  //Join key/value pairs together in url string format, encoding values.
  private function joinParams($params)
  {
    $paramString = '';
    foreach($params as $key => $val)
    {
      if($paramString !== '')
        $paramString .= '&';
      $paramString .= $key . '=' . rawurlencode($params[$key]);
    }
    return $paramString;
  }
}

