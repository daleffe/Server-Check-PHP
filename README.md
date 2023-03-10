# Server Check PHP
PHP script for RAM and CPU usage stats for server health check

For original blog post and more information visit: https://jamesbachini.com/ram-cpu-usage-php-script/

![Server Check PHP](https://jamesbachini.com/wp-content/uploads/2020/09/screenshot.png)

## Working as a library
To work with this class as a library, returning array with data, you can add ```define('SERVERCHECK_AS_LIB',TRUE);``` line.

## Helper for CodeIgniter 2
The ```servercheck_helper.php``` is a helper for CodeIgniter 2 which must be placed in the '*application/helpers*' folder and can be used as follows:
```php
<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Home extends CI_Controller {

	public function __construct() {
		parent::__construct();

		$this->load->helper('servercheck');
	}

	public function index()
	{
		print_r(servercheck());
    // - Fetch one parameter
		//print_r(servercheck('cpu_threads'));
    // - Fetch multiple parameters
		//print_r(servercheck(array('ram_usage','disk_usage','cpu_load')));
		die();
	}
}
```

... and ```servercheck.php``` must be placed at '*application/third_party*'.

## Updates
- Sept 2020 - Added dark mode emojis and got it working with Windows/WAMP;
- Feb 2020 - added JSON output using servercheck.php?json=1;
- Mar 2023 - added support to PHP environments without COM library (thanks to [Fahad Ali Khan](https://gist.github.com/fhdalikhan/c37ee69a80b11cf3f102fc4fc175733b)).
