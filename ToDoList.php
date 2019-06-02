<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title>To-Do-List</title>
</head>
<body>
<?php # Script ToDoList.php
	
	/* This page adds tasks to the tasks table.
	 * The page both displays and handles the form.
	*/
	//ini_set('display_errors', 'on');
	//error_reporting(E_ERROR | E_PARSE);
	
	// Connect the database:
	function connectDatabase($host, $username, $password, $database){
		try{
			$dbc = mysqli_connect($host, $username, $password, $database);
			if(!$dbc){throw new Exception("Database Connection Error!!");}else{ return $dbc;}
		}catch(Exception $e){
			echo "<b>Error:</b> ".$e->getMessage();
			exit();
		}
	}
	
	$dbc = connectDatabase('localhost', 'root', '', 'test');
	
	// Function for displaying a list.
	// Receives one argument: an array.
	function make_list(array $parent){
		
		// Need the main $tasks array:
		global $tasks;
		
		echo '<ol>';	// Start an unordered list
		
		// Loop through each subarray:
		foreach ($parent as $task_id => $todo){
			// Display the item:
			echo "<li><input type='checkbox' name='tasks[$task_id]' value='done'>$todo";
			
			// Check for subtasks:
			if(isset($tasks[$task_id])){
				// Call the function again
				make_list($tasks[$task_id]);
			}
			
			echo '</li>';	// Complete the list item.
		} // End of foreach loop
		
		echo '</ol>'; // close the ordered list.
	} // End of make_list() function
	
	
	// Check if the form has been submitted:
	if(($_SERVER['REQUEST_METHOD'] == 'POST') && !empty($_POST['task'])){
		
		// Sanctify the input...
		// The parent_id must be an integer:
		if(isset($_POST['parent_id']) && filter_var($_POST['parent_id'], FILTER_VALIDATE_INT, array('min_range' => 1)) ){
			$parent_id = $_POST['parent_id'];
		}else{
			$parent_id = 0;
		}
		
		// Escape the task:
		$task = mysqli_real_escape_string($dbc, strip_tags($_POST['task']));
		
		// Add the task to the database.
		$q = "INSERT INTO tasks (parent_id, task) VALUES ($parent_id, '$task')";
		$r = mysqli_query($dbc, $q);
		
		// Report on the results:
		if(mysqli_affected_rows($dbc) == 1){
			echo '<p>The task has been added!</p>';
		}else{
			echo '<p>The task could not be added!</p>';
		}
	} // End of submission IF.
	
	// Display the form:
	echo '<form action="add_task.php" method="post">
	<fieldset>
		<legend>Add a Task</legend>
		<p>Task : <input name="task" type="text" size="60" maxlength="100" required></p>
		<p>Parent Task : <select name="parent_id"><option value="0">None</option>';	
		
		// Retrieve all the uncompleted tasks:
		$q = 'SELECT task_id, parent_id, task FROM tasks WHERE date_completed="0000-00-00 00:00:00" ORDER BY parent_id, date_added ASC';
		$r = mysqli_query($dbc, $q);
		
		// Also store the tasks in an array for use later:
		$tasks = array();
		
		// Fetch the records:
		while(list($task_id, $parent_id, $task) = mysqli_fetch_array($r, MYSQLI_NUM)){
			// Add to the select menu:
			echo "<option value=\"$task_id\">$task</option>\n";
			
			// Add to the array:
			//$tasks[] = array('task_id' => $task_id, 'parent_id' => $parent_id, 'task' => $task);
			$tasks[$parent_id][$task_id] = $task;
		}
		
		// Complete the form:
		echo '</select></p>
		<input name="submit" type="submit" value="Add This Task">
		</fieldset>
		</form>';
		
		// Display all the tasks:
		echo '<h2>Current To-Do List</h2>';
		
		// Check if the form has been submitted :
		if(($_SERVER['REQUEST_METHOD'] == 'POST') && isset($_POST['tasks']) && is_array($_POST['tasks']) && !empty($_POST['tasks'])){
			// Define the query
			$q = 'UPDATE tasks SET date_completed = NOW() WHERE task_id IN(';
			
			// Add each task ID : 
			foreach($_POST['tasks'] as $task_id => $v){
				$q .= $task_id.', ';
			}
			
			// Complete the query and execute:
			$q = substr($q, 0, -2).')';
			$r = mysqli_query($dbc, $q);
			
			// Report on the results :
			if(mysqli_affected_rows($dbc) == count($_POST['tasks'])){
				echo '<p>The task(s) has been marked as completed</p>';
			}else{
				echo '<p>Not all tasks could be marked as completed!</p>';
			}
		} // End of submission IF.
		
		// Retrieve all the uncompleted tasks;
		$q = 'SELECT task_id, parent_id, task FROM tasks WHERE date_completed="0000-00-00" ORDER BY parent_id, date_added ASC';
		$r = mysqli_query($dbc, $q);
		
		// Initialize the storage array:
		$tasks = array();
		
		// Loop through the result:
		while(list($task_id, $parent_id, $task) = mysqli_fetch_array($r, MYSQLI_NUM)){
			// Add to the array:
			$tasks[$parent_id][$task_id] = $task;
		}	
		
		if(isset($tasks[0])){
			echo '<p>Check the box next to a task and click "Update" to mark a task as completed (it, and my subtasks, will no longer appear in this list).</p>
			<form action="add_task.php" method="POST">
			';
			make_list($tasks[0]);
		
			echo '<input name="submit" type="submit" value="Update" />
			</form>
			';
		}else{ echo "No work to do. Enjoy!!";}
	
?>
</body>
</html>