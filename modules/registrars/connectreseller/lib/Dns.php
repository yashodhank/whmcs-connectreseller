<?php

namespace WHMCS\Module\Registrar\ConnectReseller;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

class Dns
{
    /**
     * @param array<string, mixed> $params
     * @return mixed
     */
    public static function get($params)
    {
            try {
                $helper = new Helper();
                $tld = $params["tld"];
                $sld = $params["sld"];
                $ApiKey = $params['APIKey'];

                $domainname = DomainMapper::websiteName($params["sld"], $params["tld"]);
                $viewDomainurl = "ViewDomain/?APIKey=" . $ApiKey . '&websiteName=' . $domainname;
                $viewDomainurl = trim($viewDomainurl);
                $viewDomainurl = str_replace(' ', '%20', $viewDomainurl);

                $res = $helper->get($viewDomainurl, [], "GetDNS ViewDomain");

                if ($res['result']['responseMsg']['statusCode'] != 200) {
                    $values = $helper->sendResponse($res['result']);
                    return $values;
                }
                $res = $res['result'];

                $websiteId = $res["responseData"]['websiteId'];

                if ($res["responseData"]['dnszoneStatus'] == "1") {
                    $viewDnsUrl = trim("ViewDNSRecord/?APIKey=" . $ApiKey . '&WebsiteId=' . $websiteId);
                    $viewDnsUrl = trim($viewDnsUrl);
                    $viewDnsUrl = str_replace(' ', '%20', $viewDnsUrl);

                    $res = $helper->get($viewDnsUrl, [], "GetDNS ViewDNSRecord");

                    $viewDnsRes = $res['result'];

                    if ($viewDnsRes["responseMsg"]['statusCode'] != '200') {
                        $values = $helper->sendResponse($viewDnsRes);
                    } else {
                        $host = $viewDnsRes['responseData'];
                        foreach ($host as $v) {
                            if (($v['recordType'] == 'SRV') || ($v['recordType'] == 'SOA')  || ($v['recordType'] == 'NS')) {
                            } else {
                                $values[] = array(
                                    'hostname' => $v['recordName'],
                                    'type'     => $v['recordType'],
                                    'address'  => $v['recordContent'],
                                    'priority' => $v['recordPriority'],
                                    'recid' => $v['dnszoneRecordID']
                                );
                            }
                        }
                    }
                } else {
                    $manageDnsUrl = "ManageDNSRecords/?APIKey=" . $ApiKey . '&WebsiteId=' . $websiteId;

                    $res = $helper->get($manageDnsUrl, [], "GetDNS ManageDNSRecords");
                    $manageDnsRes = $res['result'];
                    if ($manageDnsRes["responseMsg"]['statusCode'] != '200') {
                        $values = $helper->sendResponse($manageDnsRes);
                    } else {
                        $viewDnsUrl = trim("ViewDNSRecord/?APIKey=" . $ApiKey . '&WebsiteId=' . $websiteId);
                        $viewDnsUrl = trim($viewDnsUrl);
                        $viewDnsUrl = str_replace(' ', '%20', $viewDnsUrl);

                        $res = $helper->get($viewDnsUrl, [], "GetDNS ViewDNSRecord");

                        $viewDnsRes = $res['result'];

                        if ($viewDnsRes["responseMsg"]['statusCode'] != '200') {
                            $values = $helper->sendResponse($viewDnsRes);
                        } else {
                            $host = $viewDnsRes['responseData'];
                            foreach ($host as $v) {
                                if (($v['recordType'] == 'SRV') || ($v['recordType'] == 'SOA')  || ($v['recordType'] == 'NS')) {
                                } else {
                                    $values[] = array(
                                        'hostname' => $v['recordName'],
                                        'type'     => $v['recordType'],
                                        'address'  => $v['recordContent'],
                                        'priority' => $v['recordPriority'],
                                        'recid' => $v['dnszoneRecordID']
                                    );
                                }
                            }
                        }
                    }
                }

                return $values;
            } catch (\Exception $e) {
                return array(
                    'error' => $e->getMessage(),
                );
            }
    }

    /**
     * @param array<string, mixed> $params
     * @return mixed
     */
    public static function save($params)
    {
            try {
                $helper = new Helper();
                $sld = $params['sld'];
                $tld =  $params['tld'];
                $ApiKey = $params['APIKey'];
                $websitename = $sld . '.' . $tld;
                # Put your code to get the lock status here

                $domainname = DomainMapper::websiteName($params["sld"], $params["tld"]);
                $viewDomainurl = "ViewDomain/?APIKey=" . $ApiKey . '&websiteName=' . $domainname;
                $viewDomainurl = trim($viewDomainurl);
                $viewDomainurl = str_replace(' ', '%20', $viewDomainurl);

                $res = $helper->get($viewDomainurl, [], "SaveDNS");

                // Check for errors in the res
                if ($res['result']['responseMsg']['statusCode'] != 200) {
                    $values = $helper->sendResponse($res['result']);
                    return $values;
                }
                $res = $res['result'];
                $values = array('success' => true);

                $websiteId = $res["responseData"]['websiteId'];

                if ($res["responseData"]['dnszoneStatus'] == "1") {
                    $DNSZoneId = $res["responseData"]['dnszoneId'];
                    $viewDnsUrl = "ViewDNSRecord/?APIKey=" . $ApiKey . '&WebsiteId=' . $websiteId;
                    $viewDnsUrl = trim($viewDnsUrl);
                    $viewDnsUrl = str_replace(' ', '%20', $viewDnsUrl);

                    $res = $helper->get($viewDnsUrl, [], "ViewDNSRecord");

                    $viewDnsRes = $res['result'];

                    if ($viewDnsRes["responseMsg"]['statusCode'] != '200') {
                        $values = $helper->sendResponse($viewDnsRes);
                    } else {
                        $host = $viewDnsRes['responseData'];
                        foreach ($params['dnsrecords'] as $k => $v) {
                            if (!empty($v['hostname'])  && !empty($v['address'])) {
                                if ($v['recid'] != "" && $v['recid'] != null) {
                                    $key = array_search($v['recid'], array_column($host, 'dnszoneRecordID'));
                                    if ($key != -1) {
                                        $checkHost = $host[$key];

                                        if (($v['hostname'] != $checkHost['recordName']) || ($v['type'] != $checkHost['recordType']) || ($v['address'] != $checkHost['recordContent']) || (($v['priority'] != $checkHost['recordPriority']) && $v['type'] == "MX")) {
                                            $hostName = $v['hostname'];
                                            if ($hostName == "@")
                                                $hostName = $websitename;
                                            else if ($hostName == "*")
                                                $hostName = "*." . $websitename;
                                            else if (strpos($hostName, $websitename) === false)
                                                $hostName = $hostName . "." . $websitename;
                                            $query = 'APIKey=' . $ApiKey . '&WebsiteId=' . $websiteId . '&DNSZoneID=' . $DNSZoneId . '&DNSZoneRecordID=' . $v['recid'] . '&RecordName=' . $hostName . '&RecordType=' . $v['type'] . '&RecordValue=' . $v['address'] . '&RecordTTL=43200';

                                            if ($v['type'] == "MX") {
                                                $query = $query . '&RecordPriority=' . $v['priority'];
                                            }
                                            $modifyDnsUrl = "ModifyDNSRecord/?" . $query;
                                            $modifyDnsUrl = trim($modifyDnsUrl);
                                            $modifyDnsUrl = str_replace(' ', '%20', $modifyDnsUrl);

                                            $res = $helper->get($modifyDnsUrl, [], "ModifyDNSRecord");

                                            if ($res['result']['responseMsg']['statusCode'] != 200) {
                                                $values = $helper->sendResponse($res['result']);
                                                return $values;
                                            }
                                        }
                                    }
                                } else {
                                    $status = true;
                                    $hostName = $v['hostname'];
                                    if ($hostName == "@")
                                        $hostName = $websitename;
                                    else if ($hostName == "*")
                                        $hostName = "*." . $websitename;
                                    else if (strpos($hostName, $websitename) === false)
                                        $hostName = $hostName . "." . $websitename;
                                    $key1 = array_search($hostName, array_column($host, 'recordName'));
                                    if ($key1 != -1) {
                                        $checkHost1 = $host[$key1];
                                        if ($v['address'] == $checkHost1['recordContent'])
                                            $status = false;
                                    }
                                    if ($status) {
                                        $query = 'APIKey=' . $ApiKey . '&WebsiteId=' . $websiteId . '&DNSZoneID=' . $DNSZoneId . '&RecordName=' . $hostName . '&RecordType=' . $v['type'] . '&RecordValue=' . $v['address'] . '&RecordTTL=43200';
                                        if ($v['type'] == "MX") {
                                            $query = $query . '&RecordPriority=' . $v['priority'];
                                        }
                                        $addDnsUrl = "AddDNSRecord/?" . $query;
                                        $addDnsUrl = trim($addDnsUrl);
                                        $addDnsUrl = str_replace(' ', '%20', $addDnsUrl);


                                        $res = $helper->get($addDnsUrl, [], "AddDNSRecord");

                                        if ($res['result']['responseMsg']['statusCode'] != 200) {
                                            $values = $helper->sendResponse($res['result']);
                                            return $values;
                                        }
                                    }
                                }
                            } else {
                                if ($v['recid'] != "" && $v['recid'] != null) {
                                    $query = 'APIKey=' . $ApiKey . '&DNSZoneID=' . $DNSZoneId . '&DNSZoneRecordID=' . $v['recid'];
                                    $deleteDnsUrl = "DeleteDNSRecord/?" . $query;
                                    $deleteDnsUrl = trim($deleteDnsUrl);
                                    $deleteDnsUrl = str_replace(' ', '%20', $deleteDnsUrl);

                                    $res = $helper->get($deleteDnsUrl, [], "DeleteDNSRecord");

                                    if ($res['result']['responseMsg']['statusCode'] != 200) {
                                        $values = $helper->sendResponse($res['result']);
                                        return $values;
                                    }
                                }
                            }
                        }
                    }
                }
                return $values;
            } catch (\Exception $e) {
                return array(
                    'error' => $e->getMessage(),
                );
            }
    }
}
