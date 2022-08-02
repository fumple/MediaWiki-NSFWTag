<?php
namespace MediaWiki\Extension\NSFWTag;

use \Parser;

class Util {
    public static function getUserFromParser(Parser $parser) {
        if(method_exists($parser, 'getUserIdentity')) {
            // @phan-suppress-next-line PhanUndeclaredMethod Checking the method in the function above
            return $parser->getUserIdentity();
        } else {
            return $parser->getUser();
        }
    }
}