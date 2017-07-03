<?php
/**
 * Created by PhpStorm.
 * User: fregini
 * Date: 24/02/2017
 * Time: 13:19
 */

namespace Features\Dqf\Service;

use API\V2\Exceptions\AuthenticationError;
use Features\Dqf\Service\Struct\LoginRequestStruct;
Use Features\Dqf\Service\Struct\LoginResponseStruct ;
use Log;

class Session {

    protected $email ;
    protected $password ;
    protected $sessonId ;
    protected $expires ;

    public function __construct( $email, $password ) {
        $this->email    = $email ;
        $this->password = $password ;
    }

    public function login() {
        $struct = new LoginRequestStruct() ;
        $struct->email = $this->encrypt( $this->email );
        $struct->password = $this->encrypt( $this->password );

        $client = new Client();

        $request = $client->createResource('/login', 'post', [
                'formData' => $struct->getParams(),
                'headers'  => $struct->getHeaders()
        ] );

        $client->curl()->multiExec();

        if ( $client->curl()->hasError( $request ) ) {
            throw new AuthenticationError('Login failed with message: ' . $client->curl()->getSingleContent( $request ) );
        }

        $content = json_decode( $client->curl()->getSingleContent( $request ), true );
        $response = new LoginResponseStruct( $content['loginResponse'] );

        Log::doLog(" SessionId " . $response->sessionId );

        $this->sessonId = $response->sessionId ;
        $this->expires = $response->expires ;

        return $this;
    }

    public function getSessionId() {
        if ( is_null($this->sessonId) ) {
            throw new \Exception('sessionId is null, try to login first');
        }
        return $this->sessonId ;
    }

    public function getExpires() {
        return $this->expires ;
    }

    protected function encrypt($input) {
        $size = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
        $input = $this->pkcs5_pad($input, $size);

        $key = \INIT::$DQF_ENCRYPTION_KEY ;

        $td = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, '');
        $iv = \INIT::$DQF_ENCRYPTION_IV ;

        mcrypt_generic_init($td, $key, $iv);
        $data = mcrypt_generic($td, $input);
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        $data = base64_encode($data);
        return $data;
    }

    protected function decrypt( $code ) {
        $code = base64_decode( $code ) ;

        $key = \INIT::$DQF_ENCRYPTION_KEY ;
        $iv = \INIT::$DQF_ENCRYPTION_IV ;

        $td = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', 'cbc', $iv);

        mcrypt_generic_init($td, $key, $iv);
        $decrypted = mdecrypt_generic($td, $code);

        $decrypted = $this->pkcs5_unpad( $decrypted ) ;
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);

        return utf8_encode(trim($decrypted));
    }

    protected function pkcs5_pad ($text, $blocksize) {
        $pad = $blocksize - (strlen($text) % $blocksize);
        return $text . str_repeat(chr($pad), $pad);
    }

    protected function pkcs5_unpad($text) {
        $pad = ord($text{strlen($text)-1});
        if ($pad > strlen($text)) return false;
        if (strspn($text, chr($pad), strlen($text) - $pad) != $pad) return false;
        return substr($text, 0, -1 * $pad);
    }


}