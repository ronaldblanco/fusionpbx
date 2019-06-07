<?php
if (!class_exists('sms')) {
	class sms {

		public $db;

		/**
		 * Called when the object is created
		 */

		/**
		 * Called when there are no references to a particular object
		 * unset the variables used in the class
		 */

		/**
		 * delete messages
		 */
		public function delete($messages) {
			if (permission_exists('message_delete')) {

				//delete multiple messages
					if (is_array($messages)) {
						//get the action
							foreach($messages as $row) {
								if ($row['action'] == 'delete') {
									$action = 'delete';
									break;
								}
							}
						//delete the checked rows
							if ($action == 'delete') {
								foreach($messages as $row) {
									if ($row['action'] == 'delete' or $row['checked'] == 'true') {
										$sql = "delete from v_messages ";
										$sql .= "where message_uuid = '".$row['message_uuid']."'; ";
										$this->db->query($sql);
										unset($sql);
									}
								}
								unset($messages);
							}
					}
			}
		} //end the delete function

		
		/**
		 * add messages
		 */
		public function add() {

		} //end the add function
	}  //end the class
}

?>
