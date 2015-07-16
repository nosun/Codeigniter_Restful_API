<?php

/*
    Version: 1.0
*/
class aes_encryption{

    const CIPHER = MCRYPT_RIJNDAEL_128; // Rijndael-128 is AES
    const MODE   = MCRYPT_MODE_CBC;

    public $key; // needs to be 32 bytes for aes
    public $iv; // needs to be 16 bytes for aes
    public function __construct($key = '', $iv = ''){
        $this->key = $key;
        $this->iv = $iv;
    }
    function rand_key($length = 32){
        $key = openssl_random_pseudo_bytes($length);
        return $key;
    }
    function rand_iv(){
        $ivSize = mcrypt_get_iv_size(self::CIPHER, self::MODE);
        $iv = mcrypt_create_iv($ivSize, MCRYPT_RAND);
        return $iv;
    }
    public function encrypt($plaintext){
        $ciphertext = mcrypt_encrypt(self::CIPHER, $this->key, $plaintext, self::MODE, $this->iv);
        return base64_encode($ciphertext);
    }
    public function decrypt($ciphertext){
        $ciphertext = base64_decode($ciphertext);
        $plaintext = mcrypt_decrypt(self::CIPHER, $this->key, $ciphertext, self::MODE, $this->iv);
        return rtrim($plaintext, "\0");
    }
    public function encrypt_file($input_file, $output_file){
        $input_file_handle = @fopen($input_file, "r");
        $output_file_handle = @fopen($output_file, 'wb');
        if(!$input_file_handle){ throw new Exception("Could not open input file"); }
        if(!$output_file_handle){ throw new Exception("Could not open output file"); }
        while(!feof($input_file_handle)){
            $buffer = base64_encode(fread($input_file_handle, 4096));
            $encrypted_string = $this->encrypt($buffer);
            //echo strlen($encrypted_string).'<br>';
            fwrite($output_file_handle, $encrypted_string);
        }
        fclose($input_file_handle);
        fclose($output_file_handle);
        return true;
    }
    public function decrypt_file($input_file, $output_file){
        $input_file_handle = @fopen($input_file, "r");
        $output_file_handle = @fopen($output_file, 'wb');
        if(!$input_file_handle){ throw new Exception("Could not open input file"); }
        if(!$output_file_handle){ throw new Exception("Could not open output file"); }
        while(!feof($input_file_handle)){
            //4096 bytes plaintext become 7296 bytes of encrypted base64 text
            $buffer = fread($input_file_handle, 7296);
            $decrypted_string = base64_decode($this->decrypt($buffer));
            //echo strlen($buffer).'<br>';
            fwrite($output_file_handle, $decrypted_string);
        }
        fclose($input_file_handle);
        fclose($output_file_handle);
        return true;
    }
}//class aes_encryption