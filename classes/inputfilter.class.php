<?php

/**
 * This class is designed to provide several whitelist filters for filtering
 * input from the client. It provides several utility functions usefull whenever
 * you want to secure input from various types of XSS and injection attacks.
 * 
 * TODO: Implement general token based CSRF functionality in this class
 *
 * @author Eirik Eggesbø Ottesen
 */
class CInputFilter {
    const W2P_FILTER_EMAIL = '/^(.+)@([^\(\);:,<>]+\.[a-zA-Z]{2,4})$/';
    const W2P_FILTER_LETTERS = '/^[a-zæøåA-ZÆØÅ]*$/';
    const W2P_FILTER_LETTERS_OR_NUMBERS = '/^[a-zæøåA-ZÆØÅ\.\ 0-9,]*$/';
    const W2P_FILTER_NUMBERS = '/^[0-9\.,]*$/';
    const W2P_FILTER_PRICE = '/^([0-9,.\ ]+(\.,[0-9]{2})?)$/';

    /**
     * Checks the given string against the specified pattern using preg_match.
     * Returns true if match is found, and false if string did not match the
     * pattern. The strict variable is used to specify if the function should
     * handle termination of the script immediatly if no match is found.
     * 
     * @param String $input
     * @param String $pattern
     * @param Bool $strict
     * @return Bool
     */
    public function patternVerification($input, $pattern, $strict=true) {
        global $AppUI;
        if (preg_match($pattern, $input))
            return true;
        else {
            if ($strict) {
                $AppUI->setMsg('Poisoning attempt to the URL detected. Issue logged.', UI_MSG_ALERT);
                $AppUI->redirect('m=public&a=access_denied');
            }
            else {
                return false;
            }
        }
    }

    /**
     * This function takes HTML formatted text, and removes attributes that
     * might be used to inject malicious javascript into the system.
     * It removes the illegal attributes and replaces them with only the
     * tag used.
     *
     * Thanks to Leendert W, originally posted in the php manual
     *
     * @param String $input
     * @return String
     */
    public function removeUnsafeAttributes($input) {
        $regex = '#\s*<(/?\w+)\s+(?:on\w+\s*=\s*(["\'\s])?.+? \(\1?.+?\1?\);?\1?|style=["\'].+?["\'])\s*>#is';
        return preg_replace($regex, '<${1}>', $input);
    }

    /**
     * This function strips only the specified tags from the input text.
     * 
     * thanks to Steve from the php manual for this function.
     * @param String $str
     * @param Array $tags
     * @return String
     */
    public static function stripOnly($str, $tags) {
        if (!is_array($tags)) {
            $tags = (strpos($str, '>') !== false ? explode('>', str_replace('<', '', $tags)) : array($tags));
            if (end($tags) == '')
                array_pop($tags);
        }
        foreach ($tags as $tag)
            $str = preg_replace('#</?' . $tag . '[^>]*>#is', '', $str);
        return $str;
    }

}

?>
