<?php

class WidgetApi{

	protected $subdomain = 'xxxx';
    protected $client_id = 'xxxx';
    protected $client_secret = 'xxxx';
    protected $code = 'xxxx';
    protected $redirect_uri = 'xxxx';

	function __construct ($config) 
	{					
		$this->subdomain        = $config['subdomain'];
        $this->client_id        = $config['client_id'];
        $this->client_secret    = $config['client_secret'];      
        $this->redirect_uri     = $config['redirect_uri'];
	}
	
	/**
	 * Saving an access token to a file	 	  
	 */
    private function saveAccessToken( $data = '' )
	{		
		$filename 	= 'includes/accessToken.txt';			
		file_put_contents($filename, $data, LOCK_EX);
	}
	
	/**
	 * Get an access token	 	  
	 */

	private function getAccessToken( $authCode )
	{
		$link = 'https://' . $this->$subdomain . '.amocrm.ru/oauth2/access_token'; //Формируем URL для запроса

        /** Соберем данные для запроса */
        $data = [
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'grant_type' => 'authorization_code',
            'code' => $authCode,
            'redirect_uri' => $this->redirect_uri,
        ];

        /**
         * Нам необходимо инициировать запрос к серверу.
         * Воспользуемся библиотекой cURL (поставляется в составе PHP).
         * Вы также можете использовать и кроссплатформенную программу cURL, если вы не программируете на PHP.
         */
        $curl = curl_init(); //Сохраняем дескриптор сеанса cURL
        /** Устанавливаем необходимые опции для сеанса cURL  */
        curl_setopt($curl,CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-oAuth-client/1.0');
        curl_setopt($curl,CURLOPT_URL, $link);
        curl_setopt($curl,CURLOPT_HTTPHEADER,['Content-Type:application/json']);
        curl_setopt($curl,CURLOPT_HEADER, false);
        curl_setopt($curl,CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curl,CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($curl,CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($curl,CURLOPT_SSL_VERIFYHOST, 2);
        $out = curl_exec($curl); //Инициируем запрос к API и сохраняем ответ в переменную
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        /** Теперь мы можем обработать ответ, полученный от сервера. Это пример. Вы можете обработать данные своим способом. */
        $code = (int)$code;
        $errors = [
            400 => 'Bad request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not found',
            500 => 'Internal server error',
            502 => 'Bad gateway',
            503 => 'Service unavailable',
        ];

        // try
        // {
        //     /** Если код ответа не успешный - возвращаем сообщение об ошибке  */
        //     if ($code < 200 || $code > 204) {
        //         throw new Exception(isset($errors[$code]) ? $errors[$code] : 'Undefined error', $code);
        //     }
        // }
        // catch(Exception $e)
        // {
        //     die('Ошибка: ' . $e->getMessage() . PHP_EOL . 'Код ошибки: ' . $e->getCode());
        // }

        /**
         * Данные получаем в формате JSON, поэтому, для получения читаемых данных,
         * нам придётся перевести ответ в формат, понятный PHP
         */
       
        return $out;

        // $access_token = $response['access_token']; //Access токен
        // $refresh_token = $response['refresh_token']; //Refresh токен
        // $token_type = $response['token_type']; //Тип токена
        // $expires_in = $response['expires_in']; //Через сколько действие токена истекает
	}	
				
				
    public function handler($GGET)
    {
        $data = array();        				
       
		$data['action'] 		= isset($_GET['action']) 		? trim($_GET['action'])     : null;	 	
		$data['operation'] 		= isset($_GET['operation']) 	? trim($_GET['operation'])  : null;  
        $data['auth_code'] 	    = isset($_GET['auth_code']) 	? trim($_GET['auth_code'])  : null;  

        if( $data['action'] != 'get_access_token' && $data['action'] != 'read_leads' )
        {
            $Response = array('status' => 'fail','message' => 'This Action is Invalid');
            $Output = json_encode($Response);
            return $Output;
        }

        switch($data['action'])
        {
            
            default;
            break;

			case 'get_access_token':  // Запись и чтение маски для ALC для парсинга услуг. В случае operation=read возвращает из БД
				
				
                if($data['auth_code'] == '')
				{
					$Response = array('status' => 'fail','message' => 'There are missing fields! Please check your API Manual');
					$Output = json_encode($Response);
					return $Output;
				}

                $accessToken = getAccessToken( $data['auth_code'] );
                
                $response = array(
                    'status' 	    => 'success',
                    'action' 	    => $data['action'],
                    'access_token'	=> $accessToken
                );

                $output = json_encode($response);
                return $output;	
        }
    }
?>