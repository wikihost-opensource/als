<?php

namespace Client\Websocket\Commands;

class Ping extends Base
{
    public function execute(array $args)
    {

        $domain = array_shift($args);
        $ipv6Only = false;

        if (filter_var($domain, FILTER_VALIDATE_IP)) {
            $host = $domain;
        } elseif ($ipv6Only) {
            $records = dns_get_record($domain, DNS_AAAA);
            if ($records && isset($records[0]['ipv6'])) {
                $host = $records[0]['ipv6'];
            } else {
                return $this->end();
            }
        } else {
            $records = dns_get_record($domain, DNS_A);
            if ($records && isset($records[0]['ip'])) {
                $host = $records[0]['ip'];
            } else {
                $records = dns_get_record($domain, DNS_AAAA);
                if ($records && isset($records[0]['ipv6'])) {
                    $host = $records[0]['ipv6'];
                } else {
                    return $this->end();
                }
            }
        }
        

        $this->client->send("{$this->commandIndex}|1|{$domain}|{$this->ticket}");

        $total = 10;
        $process = new \Process('/bin/ping', ['-O', '-c 10', $host]);
        $hasHeader = false;
        $counter = 0;
        while ($process->run()) {
            if (!$hasHeader) {
                $hasHeader = true;
                $process->getOutput();
            }
            if ($counter >= $total) $process->getOutput();
            $content = $process->getOutput();
            if (empty($content)) continue;

            $counter++;

            preg_match_all('/icmp_seq=(\d+) ttl=(\d+) time=(\d+.\d+) ms/m', $content, $matches, PREG_SET_ORDER, 0);
            if (empty($matches)) {
                $this->send("1|{$host}|-1");
            } else {
                $this->send("1|{$host}|{$matches[0][1]}|{$matches[0][2]}|{$matches[0][3]}");
            }
        }

        return $this->end();
    }
}
