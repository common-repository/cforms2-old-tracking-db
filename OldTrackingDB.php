<?php
/**
 * Copyright (c) 2012 Oliver Seidel (email : oliver.seidel @ deliciousdays.com)
 * Copyright (c) 2017 Bastian Germann
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace Cforms2;

class OldTrackingDB
{
    private function createTables()
    {
        include_once ABSPATH . 'wp-admin/includes/upgrade.php';
        global $wpdb;

        $sql = "CREATE TABLE {$wpdb->prefix}cformssubmissions (
                id int(11) unsigned auto_increment,
                form_id varchar(3) default '',
                sub_date timestamp,
                email varchar(40) default '',
                ip varchar(47) default '',
                PRIMARY KEY  (id) ) " . $wpdb->get_charset_collate() . ";";
        dbDelta($sql);

        $sql = "CREATE TABLE {$wpdb->prefix}cformsdata (
                f_id int(11) unsigned auto_increment primary key,
                sub_id int(11) unsigned NOT NULL,
                field_name varchar(100) NOT NULL default '',
                field_val text) " . $wpdb->get_charset_collate() . ";";
        dbDelta($sql);
    }

    private function getIp()
    {
        if (isset($_SERVER)) {
            if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
                $ip_addr = $_SERVER["HTTP_X_FORWARDED_FOR"];
            } elseif (isset($_SERVER["HTTP_CLIENT_IP"])) {
                $ip_addr = $_SERVER["HTTP_CLIENT_IP"];
            } else {
                $ip_addr = $_SERVER["REMOTE_ADDR"];
            }
        } else {
            if (getenv('HTTP_X_FORWARDED_FOR')) {
                $ip_addr = getenv('HTTP_X_FORWARDED_FOR');
            } elseif (getenv('HTTP_CLIENT_IP')) {
                $ip_addr = getenv('HTTP_CLIENT_IP');
            } else {
                $ip_addr = getenv('REMOTE_ADDR');
            }
        }
        return $ip_addr;
    }

    public function writeTrackingRecord($track)
    {
        global $wpdb;

        $additional_fields = array();
        $dosave = false;
        foreach ($track['data'] as $key => $value) {
            // clean up keys
            if (preg_match('/\$\$\$/', $key) || strpos($key, '[*') !== false) {
                continue;
            }

            if (strpos($key, 'cf_form') !== false && preg_match('/^cf_form\d*_(.+)/', $key, $r)) {
                $key = $r[1];
            }

            if (strpos($key, '___') !== false && preg_match('/^(.+)___\d+/', $key, $r)) {
                $key = $r[1];
            }


            $additional_fields[$key] = $value;
            $dosave = true;
        }
        if (!$dosave) {
            return;
        }

        $wpdb->insert(
            "{$wpdb->prefix}cformssubmissions",
            array(
                'form_id' => $track['id'],
                'email' => $track['email'],
                'ip' => $this->getIp(),
                'sub_date' => current_time('Y-m-d H:i:s')
            )
        );

        $subID = $wpdb->get_row("SELECT LAST_INSERT_ID() as number from {$wpdb->prefix}cformssubmissions;");
        $subID = ($subID->number == '') ? '1' : $subID->number;

        $sql = "INSERT INTO {$wpdb->prefix}cformsdata (sub_id,field_name,field_val) VALUES";
        $sep = ' ';
        foreach ($additional_fields as $key => $value) {
            $sql .= $sep . $wpdb->prepare('(%s,%s,%s)', $subID, $key, $value);
            $sep = ',';
        }

        $wpdb->query($sql);
    }

    public function __invoke()
    {
        global $wpdb;

        if ($wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}cformssubmissions'") === "{$wpdb->prefix}cformssubmissions") {
            add_action('cforms2_after_processing_action', array($this, 'writeTrackingRecord'));
        } else {
            $this->createTables();
        }
    }

    public static function getEntries($fname, $from, $to, $sort, $limit, $sortdir, $limitstart)
    {
        global $wpdb;

        // unify
        if ($sort === 'date' || $sort === 'timestamp') {
            $sort = 'sub_date';
        } elseif ($sort === 'form') {
            $sort = 'form_id';
        }

        $limit = empty($limit) ? '' : 'LIMIT ' . ((int) $limitstart) . ',' . (int) $limit;

        $sortdir = strtolower($sortdir) === 'asc' ? 'asc' : 'desc';


        $simple_order = $cfsort = '';
        if (in_array($sort, array('id', 'form_id', 'sub_date', 'email', 'ip'))) {
            $simple_order = "ORDER BY " . $sort . ' ' . $sortdir;
        } else {
            $simple_order = "ORDER BY id DESC";
            $cfsort = $sort;
        }

        // SORT
        $cfdata = array();
        $cfsortdir = $sortdir;

        // GENERAL WHERE
        $where = false;

        $fname_in = '';
        for ($i = 1; $i <= count(FormSettings::forms()); $i++) {
            $fnames[$i] = stripslashes(FormSettings::form($i)->name());
            if ($fname && preg_match('/' . $fname . '/i', $fnames[$i])) {
                $fname_in .= "'$i'" . ',';
            }
        }

        if (!empty($fname)) {
            $where = empty($fname_in) ? " form_id='-1'" : ' form_id IN (' . substr($fname_in, 0, -1) . ')';
        }
        $where .= $from ? ($where ? ' AND' : '') . $wpdb->prepare(" sub_date > '%s'", $from) : '';
        $where .= $to ? ($where ? ' AND' : '') . $wpdb->prepare(" sub_date < '%s'", $to) : '';
        $where = $where ? 'WHERE' . $where : '';

        $in = '';

        $sql = "SELECT *, UNIX_TIMESTAMP(sub_date) as rawdate  FROM {$wpdb->prefix}cformssubmissions $where $simple_order $limit";
        $all = $wpdb->get_results($sql);

        foreach ($all as $d) {
            $in .= $wpdb->prepare("%d,", $d->id);
            $n = ( $d->form_id == '' ) ? 1 : $d->form_id;
            $cfdata[$d->id]['id'] = $d->id;
            $cfdata[$d->id]['form'] = $fnames[$n];
            $cfdata[$d->id]['date'] = $d->sub_date;
            $cfdata[$d->id]['timestamp'] = $d->rawdate;
            $cfdata[$d->id]['email'] = $d->email;
            $cfdata[$d->id]['ip'] = $d->ip;
        }

        if ($in == '') {
            return array();
        }

        $sql = "SELECT * FROM {$wpdb->prefix}cformsdata WHERE sub_id IN (" . substr($in, 0, -1) . ")";
        $all = $wpdb->get_results($sql);
        $offsets = array();

        foreach ($all as $d) {
            if (isset($offsets[$d->sub_id][$d->field_name]) && !empty($offsets[$d->sub_id][$d->field_name])) {
                $offsets[$d->sub_id][$d->field_name] ++;
            } else {
                $offsets[$d->sub_id][$d->field_name] = 1;
            }

            $tmp = '';
            if ($offsets[$d->sub_id][$d->field_name] > 1) {
                $tmp = '-' . $offsets[$d->sub_id][$d->field_name];
            }

            $cfdata[$d->sub_id]['data'][$d->field_name . $tmp] = $d->field_val;
        }

        if (!empty($cfsort)) {
            $cfdataTMP = $cfdata;
            uksort(
                $cfdata,
                function ($a, $b) {

                    if (!is_array($a) && !is_array($b)) {
                        $na = empty($cfdataTMP[$a]['data'][$cfsort]) ? false : $cfdataTMP[$a]['data'][$cfsort];
                        $nb = empty($cfdataTMP[$b]['data'][$cfsort]) ? false : $cfdataTMP[$b]['data'][$cfsort];

                        if (!($na && $nb)) {
                            if (!$na) {
                                return 1;
                            } elseif (!$nb) {
                                return -1;
                            }
                            return 0;
                        }
                    }

                    $tmpA = (int) trim($na);
                    $tmpB = (int) trim($nb);
                    if (is_numeric($na) && is_numeric($nb)) {
                        if (stristr($cfsortdir, 'asc') === false) {
                            return ($tmpB > $tmpA) ? -1 : 1;
                        } else {
                            return ($tmpA < $tmpB) ? -1 : 1;
                        }
                    } else {
                        if (stristr($cfsortdir, 'asc') === false) {
                            return strcasecmp($nb, $na);
                        } else {
                            return strcasecmp($na, $nb);
                        }
                    }
                }
            );
        }
        return $cfdata;
    }
}
