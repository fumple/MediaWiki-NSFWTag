<?php
namespace MediaWiki\Extension\NSFWTag;

use MediaWiki\User\UserOptionsManager;
use \RequestContext;
use \Parser;
use \ParserOptions;

class Util {
    public static function getUserFromParserOptions(ParserOptions $parserOptions) {
        if(method_exists($parserOptions, 'getUserIdentity')) {
            // @phan-suppress-next-line PhanUndeclaredMethod Checking the method in the function above
            return $parserOptions->getUserIdentity();
        } else {
            return $parserOptions->getUser();
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
            // Use lazy load function to do the "hard" work
            $parserOption = self::lazyLoadNSFWPreference($optionsManager, $parser->getOptions());

            // Set output extension data, so it knows to render the header/footer messages
            $parser->getOutput()->setExtensionData('extnsfwtag', $parserOption);

            return $parserOption == '1';
        } else {
            // convert 1/0 to true-false
            $preference = $parserOption == '1';

            // If extension data is null, the option was lazy loaded
            // set output extension data, as it can't be set by the lazy load function
            if($parser->getOutput()->getExtensionData('extnsfwtag') == null) {
                $parser->getOutput()->setExtensionData('extnsfwtag', $preference ? '1' : '0');
            }

            return $preference;
        }
    }

    /**
     * Get NSFW preference without a Parser object
     * 
     * @param  UserOptionsManager $optionsManager
     * @param  ParserOptions $parserOptions
     * @return string
     */
    public static function lazyLoadNSFWPreference( UserOptionsManager $optionsManager, ParserOptions $parserOptions )
    {
        // Couldn't find a way to get a context from the arguments provided
        $request = RequestContext::getMain()->getRequest();
        $user = self::getUserFromParserOptions($parserOptions);

        // Check whether the user enabled the checkbox in the editor
        $NSFWTogglePreference = $optionsManager->getBoolOption($user, 'nsfwtag-prefeditor');

        // load NSFW preference and save as 1/0
        if ($parserOptions->getIsPreview() && $NSFWTogglePreference) {
            $preference = $request->getCheck('shownsfwcheckbox');
        } else if ($request->getCheck('shownsfw')) {
            $preference = $request->getBool('shownsfw');
        } else {
            $preference = $optionsManager->getBoolOption($user, 'nsfwtag-pref');
        }

        $parserOptions->setOption('extnsfwtag', $preference ? '1' : '0');
        return $preference ? '1' : '0';
    }
}