<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Configurable Reports
 * A Moodle block for creating customizable reports
 * @package block_configurable_reports
 * @author David Pesce <davidpesce@gmail.com>
 * @date 2019
 */

require_once($CFG->dirroot.'/blocks/configurable_reports/plugin.class.php');

class plugin_percent extends plugin_base {

	public function init() {
		$this->form = true;
		$this->unique = false;
		$this->fullname = get_string('percent','block_configurable_reports');
		$this->reporttypes = array('courses','users','sql','timeline','categories');
	}

	public function summary($data) {
		global $CFG;

		if ($this->report->type != 'sql') {
			$components = cr_unserialize($this->report->components);
			if (!is_array($components) || empty($components['columns']['elements'])) {
				print_error('nocolumns');
			}

			$columns = $components['columns']['elements'];
			$i = 0;
			foreach ($columns as $c) {
				if ($i == $data->column) {
					return $c['summary'];
				}
				$i++;
			}
		} else {
			require_once($CFG->dirroot.'/blocks/configurable_reports/report.class.php');
			require_once($CFG->dirroot.'/blocks/configurable_reports/reports/'.$this->report->type.'/report.class.php');

			$reportclassname = 'report_'.$this->report->type;
			$reportclass = new $reportclassname($this->report);

			$components = cr_unserialize($this->report->components);
			$config = (isset($components['customsql']['config']))? $components['customsql']['config'] : new stdclass;

			if (isset($config->querysql)) {
				$sql =$config->querysql;
				[$sql, $params] = $reportclass->prepare_sql($sql);
				if ($rs = $reportclass->execute_query($sql, $params)) {
					foreach ($rs as $row) {
						$i = 0;
						foreach ($row as $colname=>$value) {
							if ($i == $data->column) {
								return str_replace('_', ' ', $colname);
							}
							$i++;
						}
						break;
					}
					$rs->close();
				}
			}
		}

		return '';
	}

	public function execute($rows) {
		$result = 0;
		$totalrows = 0;
		$resultrows = 0;
		foreach ($rows as $r) {
			$r = (is_numeric($r)) ? $r : 0;
			if ($r >= 1) {
			    $resultrows++;
			}
			$totalrows++;
		}
		if ($totalrows > 0) {
			$result = round(($resultrows / $totalrows) * 100, 2);
		}
		return $result . ' %';
	}
}
