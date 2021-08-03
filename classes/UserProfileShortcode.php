<?php

namespace PO\classes;
use PO\classes\ContactsUtils;
use PO\services\WAService;
use PO\services\SettingsService;

class UserProfileShortcode {
    private static $SHORTCODE_NAME = 'wa-profile';

    public function __construct() {

        $this->registerShortcode();
    }

    private function registerShortcode() {
        add_shortcode(self::$SHORTCODE_NAME, array($this, "waUserProfileShortcode"));
    }

    public function waUserProfileShortcode($args, $content = null)
    {
        $userID_arg = $this->extractAndRemoveUserID($args);
        if(empty($_REQUEST['user-id']) && !$userID_arg) {
            return;
        }

        $userID = '';
        if (empty($_REQUEST['user-id'])) {
            $userID = $userID_arg;
        } else {
            $userID = sanitize_key($_REQUEST['user-id']);
        }

        $filterStatement = array("'User ID' eq ${userID}");

        $filter = ContactsUtils::generateFilterStatement($filterStatement);
        $select = ContactsUtils::generateSelectStatement($args);

        $sites = array_map('trim', explode(',', $args['sites']));
        unset($args['sites']);

        $sites = empty($sites) ? reset($waAPIKeys) : $sites;

        $waService = new WAService();
        $contacts = $waService->getContactsList($filter, $select);

        $contacts = new Contacts($contacts);
        
        $contacts->filterFieldValues($args);

        $contacts = $contacts->getFieldValuesOnly();

        return $this->render($contacts[0]);
    }

    private function render($userProfile=NULL, $class="wa-profile") {
        ob_start();

        if (empty($userProfile)) {
            echo "Nothing to show.";
            return ob_get_clean();
        }

        echo "<div class=\"${class}\">";
        foreach ($userProfile as $userFields) {

            $userFieldName = sanitize_title_with_dashes($userFields['FieldName']);
            $userFieldValue = $userFields['Value'];
            $userFieldNameLabel = htmlspecialchars($userFields['FieldName']);

            if (is_array($userFieldValue)) {
                $userFieldValue = $userFieldValue['Label'];
            }

            if (empty($userFieldName) || empty($userFieldValue)) {
                continue;
            }

            echo "<div class=\"${userFieldName}\" data-wa-label=\"${userFieldNameLabel}\">";
            echo htmlspecialchars($userFieldValue);
            echo "</div>";
        }
        echo "</div>";

        return ob_get_clean();
    }

    private function extractAndRemoveUserID(&$shortCodeArgs) {
        $userID = isset($shortCodeArgs['user-id']) ? $shortCodeArgs['user-id'] : "";
        unset($shortCodeArgs['user-id']);
        return $userID;
    }

}

?>