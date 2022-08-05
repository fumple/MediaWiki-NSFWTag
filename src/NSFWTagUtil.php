<?php
namespace MediaWiki\Extension\NSFWTag;

use MediaWiki\User\UserOptionsManager;
use \RequestContext;
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

    /**
     * Get NSFW preference and save it in a Parser
     * 
     * @param  UserOptionsManager $optionsManager
     * @param  Parser $parser
     * @return boolean
     */
    public static function getNSFWPreference( UserOptionsManager $optionsManager, Parser $parser )
    {
        // Check whether the extnsfwtag option was already set
        $parserOption = $parser->getOptions()->getOption('extnsfwtag');

        // If not set
        if ($parserOption == '') {
            // Couldn't find a way to get a context from the arguments provided
            $request = RequestContext::getMain()->getRequest();
            $user = self::getUserFromParser($parser);

            // Check whether the user enabled the checkbox in the editor
            $NSFWTogglePreference = $optionsManager->getBoolOption($user, 'nsfwtag-prefeditor');

            // load NSFW preference and save as 1/0
            if ($parser->getOptions()->getIsPreview() && $NSFWTogglePreference) {
                $preference = $request->getCheck('shownsfwcheckbox');
            } else if ($request->getCheck('shownsfw')) {
                $preference = $request->getBool('shownsfw');
            } else {
                $preference = $optionsManager->getBoolOption($user, 'nsfwtag-pref');
            }

            $parser->getOptions()->setOption('extnsfwtag', $preference ? '1' : '0');
            $parser->getOutput()->setExtensionData('extnsfwtag', $preference ? '1' : '0');
            return $preference;
        } else {
            // convert 1/0 to true-false
            return $parserOption == '1';
        }
    }
}