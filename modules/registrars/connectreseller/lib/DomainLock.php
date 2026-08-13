<?php

namespace WHMCS\Module\Registrar\ConnectReseller;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

class DomainLock
{
    /**
     * @param array<string, mixed> $params
     * @param bool $locked
     * @param bool $clientArea
     * @return array<string, mixed>
     */
    public static function setLock(Helper $helper, array $params, $locked, $clientArea = false)
    {
        $domainname = DomainMapper::websiteName($params['sld'], $params['tld']);
        $viewUrl = 'ViewDomain/?APIKey=' . $params['APIKey'] . '&websiteName=' . $domainname;
        $action = $locked ? 'lock' : 'unlock';
        $response = $helper->get($viewUrl, array(), $action . ' ViewDomain');

        if ($response['result']['responseMsg']['statusCode'] != 200) {
            if ($clientArea) {
                return array(
                    'templatefile' => 'connectreseller',
                    'vars' => array(
                        'error' => 'Error: ' . $response['result']['responseMsg']['statusCode'] . ' '
                            . $response['result']['responseMsg']['message'],
                    ),
                );
            }

            return $helper->sendResponse($response['result']);
        }

        $domainNameId = $response['result']['responseData']['domainNameId'];
        $flag = $locked ? 'true' : 'false';
        $manageUrl = 'ManageDomainLock?APIKey=' . $params['APIKey']
            . '&domainNameId=' . $domainNameId
            . '&websiteName=' . $domainname
            . '&isDomainLocked=' . $flag;
        $res = $helper->get($manageUrl, array(), $action);

        if ($res['result']['responseMsg']['statusCode'] != 200) {
            $message = 'Error: ' . $res['result']['responseData']['statusCode'] . ' '
                . $res['result']['responseData']['message'];
            if ($clientArea) {
                return array(
                    'templatefile' => 'connectreseller',
                    'vars' => array('error' => $message),
                );
            }

            return array('error' => $message);
        }

        if ($clientArea) {
            return array(
                'templatefile' => 'connectreseller',
                'vars' => array('successful' => 'The changes to the domain were saved successfully'),
            );
        }

        return array('success' => 'success');
    }
}
