<?

if (!class_exists('check_acl')) {
	class check_acl {

		private $allow_nodes;

		public function __construct($db, $domain_name) {

			// Example of output
			//default_policy	node_policy		node_cidr
			//deny				deny			1.1.1.1/32
			//deny				allow			0.0.0.0/0

			$sql =  "SELECT v_access_controls.access_control_default as default_policy";
			$sql .= " v_access_control_nodes.node_type as node_policy";
			$sql .= " v_access_control_nodes.node_cidr as node_cidr ";
			$sql .= "FROM v_access_control_nodes JOIN v_access_controls";
			$sql .= " ON v_access_controls.access_control_uuid = v_access_control_nodes.access_control_uuid ";
			$sql .= "WHERE v_access_controls.access_control_name = 'sms'";
			$sql .= " AND v_access_control_nodes.node_domain = '" . $domain_name . "'";
			$sql .= " ORDER BY node_policy DESC";

		}

		function check_acl_1() {
			global $db, $debug, $domain_uuid, $domain_name;

			//select node_cidr from v_access_control_nodes where node_cidr != '';
			$sql = "select node_cidr from v_access_control_nodes where node_cidr != '' and node_type = 'allow'";
			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();
			$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
			if (count($result) == 0) {
				die("No ACL's");
			}
			foreach ($result as &$row) {
				$allowed_ips[] = $row['node_cidr'];
			}

			$acl = new IP4Filter($allowed_ips);

			return $acl->check($_SERVER['REMOTE_ADDR'], $allowed_ips);
		}

		function ipCIDRCheck ($IP, $CIDR) {
			list ($net, $mask) = split ("/", $CIDR);
		
			$ip_net = ip2long ($net);
			$ip_mask = ~((1 << (32 - $mask)) - 1);
		
			$ip_ip = ip2long ($IP);
		
			$ip_ip_net = $ip_ip & $ip_mask;
		
			return ($ip_ip_net == $ip_net);
		}
	}
}
?>