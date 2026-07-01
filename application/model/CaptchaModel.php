<?php

/**
 * Class CaptchaModel
 *
 * This model class handles all the captcha stuff.
 * Currently this uses the excellent Captcha generator lib from https://github.com/Gregwar/Captcha
 * Have a look there for more options etc.
 */
class CaptchaModel
{
    /**
     * Generates the captcha, "returns" a real image, this is why there is header('Content-type: image/jpeg')
     * Note: This is a very special method, as this is echoes out binary data.
     */
    public static function generateAndShowCaptcha()
    {
        // create a captcha with the CaptchaBuilder lib (loaded via Composer)
        $captcha = new Gregwar\Captcha\CaptchaBuilder;
        $captcha->build(
            Config::get('CAPTCHA_WIDTH'),
            Config::get('CAPTCHA_HEIGHT')
        );

        // write the captcha character into session
        Session::set('captcha', $captcha->getPhrase());

        // render an image showing the characters (=the captcha)
        header('Content-type: image/jpeg');
        $captcha->output();
    }

    /**
     * Checks if the entered captcha is the same like the one from the rendered image which has been saved in session
     * @param $captcha string The captcha characters
     * @return bool success of captcha check
     */
    public static function checkCaptcha($captcha)
    {
        if (Session::get('captcha') && ($captcha == Session::get('captcha'))) {
            return true;
        }

        return false;
    }

    /**
     * Verifies a Google reCAPTCHA v2 response token server-side.
     * The token ($_POST['g-recaptcha-response']) is generated in the browser by the reCAPTCHA widget
     * and sent together with our secret key to Google's siteverify endpoint. Google answers with a JSON
     * object; we only continue if "success" is true.
     *
     * @param string $recaptcha_response The value of the hidden "g-recaptcha-response" field from the form
     * @return bool true if Google confirms the captcha, false otherwise
     */
    public static function checkReCaptcha($recaptcha_response)
    {
        // no token at all (e.g. user did not solve the captcha) -> fail early
        if (empty($recaptcha_response)) {
            return false;
        }

        // data sent to Google: our private secret key + the token from the browser + the user's IP
        $post_data = http_build_query(array(
            'secret'   => Config::get('RECAPTCHA_SECRET_KEY'),
            'response' => $recaptcha_response,
            'remoteip' => $_SERVER['REMOTE_ADDR']
        ));

        // server-to-server POST request to Google's verification endpoint via cURL
        $curl = curl_init('https://www.google.com/recaptcha/api/siteverify');
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        // local XAMPP usually has no up-to-date CA bundle, so we skip peer verification here
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($curl);
        curl_close($curl);

        // decode Google's JSON answer and return the success flag
        $result = json_decode($response, true);

        return isset($result['success']) && $result['success'] === true;
    }
}
