<?php

/**
 * File: getdirectorylist.inc.php
 * @package directory_info
 */


/**
 * Generic (re-usable) class to retrieve information on files / directories
 *
 * Class which can retrieve information on files, sub-directories and files within
 * sub-directories.
 *
 * You can retrieve various kinds of lists with filenames as well as retrieve information
 * about individual files, such as the file-size, file-last-access-date, file-last-modified-date
 * and more.
 *
 * The class defaults to image files only, but based on the parameters you pass,
 * information on any type of file can be retrieved.
 *
 * *********************************************************************<br>
 * <b>Features</b><br>
 * *********************************************************************
 *
 * - Generate a filelist / directory list as an array
 * - Include subdirectories / files within subdirectories ($recursion = true)
 * - Create a selection within the generated filelist based on:<br>
 * 			* file extension(s)<br>
 * 			* file extension(s) + mimetype check<br>
 * 			* last modified date<br>
 * 			* last access date
 * - Create a selection within an earlier made selection
 * - Sort the filelist
 * - Find out which is the most recently changed file + the timestamp
 * - Find out the directory size of<br>
 * 			* a directory<br>
 * 			* a directory and all subdirectories within it<br>
 * 			* a selection of files within a directory<br>
 * 			* same but then including selected files in subdirectories
 * - Check whether the file extension of a file is within an allowed list (defaults to image files)
 * - Check whether the mime-type of a file is within an allowed list (defaults to image files)
 * - Combine the above two checks
 * - Get information on individual files:<br>
 * 			* filesize (optionally in a human readable format)<br>
 * 			* last modified date (optionally in a - self-defined - human readable format)<br>
 * 			* last access date (optionally in a - self-defined - human readable format)<br>
 * 			* file permissions in a human readable format<br>
 * 			* file owner id<br>
 * 			* mime-type
 * - Change an arbitrary filesize to a human readable format
 *
 * *********************************************************************<br>
 * <b>Basics on how to use the class</b><br>
 * *********************************************************************
 *
 * <i>How to instantiate ?</i>
 * <code><?php
 * $dirobj = new directory_info( );
 * ?></code>
 *
 * <i>How to view the results ?</i><br>
 * You can format the results display yourself, to quickly retrieve and view a filelist, you can use:
 * <code><?php
 * // Get a filelist for $pathtodir, $recursion = true,
 * // don't use a previously made selection (null)
 * $dirobj->get_filelist( null, $pathtodir, true );
 * print 'filecount is : ' . $dirobj->filecount;
 * print '<pre>';
 * print_r( $dirobj->filelist );
 * print '</pre>';
 * ?></code>
 *
 * <i>Note</i>: The directory path passed to a methods should be relative to the location
 * of the calling file and should end with a trailing slash
 * <code><?php
 * $pathtodir = 'images/';
 * ?></code>
 *
 * <b>For more extended examples of how to use this class, have a look at the example file which came
 * with this class. It should be located in /example/example.php</b>
 *
 * *********************************************************************<br>
 * <b>Version management information</b><br>
 * *********************************************************************
 *
 * + v1.5 2006-09-02<br>
 * 			- Improved inline documentation to work with {@link http://www.phpdoc.org/ phpDocumentor}
 * 			and updated the example file<br>
 * 			- Created separate methods to set the class variables and moved the main filelist get
 * 			functionality out of the class instantiation<br>
 * 			- Created various methods for retrieving selections of a filelist
 * 			- Adjusted the methods to auto-re-use or auto-destroy the previous retrieved
 * 			results so you can keep re-using the (instantiated) class<br>
 * 			- Made a lot of file related methods static, i.e. callable without instantiating
 * 			the class<br>
 * 			- Added lots of new methods
 * + v1.4 2006-07-04<br>
 * 			- Added filecount variable
 * + v1.3 2005-12-24<br>
 * 			- Added support for uppercase filenames and uppercase extensions passed
 * + v1.2 2005-12-05<br>
 * 			- Rewrite of function to class to improve re-usability<br>
 *			- Added filesize option
 * + v1.0 2004-07-15<br>
 * 			- Originally posted as a function to the comments section
 * 			of {@link http://www.php.net/function.readdir}
 *
 * *********************************************************************
 *
 * @package directory_info
 * @author	Juliette Reinders Folmer, {@link http://www.adviesenzo.nl/ Advies en zo} -
 *  <getdirectorylist@adviesenzo.nl>
 *
 * @todo 	Check whether the $pathtofile checks can be removed
 *
 * @version	1.5
 * @since	2006-09-02 // Last changed: by Juliette Reinders Folmer
 * @copyright	Advies en zo, Meedenken en -doen ©2004-2006
 * @license http://www.opensource.org/licenses/lgpl-license.php GNU Lesser General Public License
 * @license	http://opensource.org/licenses/academic Academic Free License Version 1.2
 * @example	example/example.php
 *
 */
class directory_info {

	/**********************************************************************
	 * INITIATION OF THE DEFAULT VARIABLES USED IN THE CLASS
	 *********************************************************************/

	/**
	 * Directory to traverse
	 *
	 * - Relative path to a directory, i.e. relative to the calling file
	 * - IMPORTANT: end the path with a trailing '/' !!!
	 * - Defaults to the current directory
	 *
	 * @var		string	$pathtodir
	 */
	var $pathtodir = './';

	/**
	 * Only show 'safe' files/directories ?
	 *
	 * - <i>true</i> means: only show 'safe' files (not .htaccess or references to higher
	 * 			directories)
	 * - <i>false</i> means: show all files
	 * - Defaults to <i>true</i>
	 *
	 * @var		bool	$safe_exts
	 **/
	var $safe_exts = true;

	/**
	 * Array of file extensions
	 *
	 * The list is used to determine which files should be included in the result array if you want
	 * to make a selection based on file extensions
	 * - Should be an array of file extensions to show only files which comply with these extensions
	 * - Alternatively, the special string-value <i>all</i> will show files independent
	 * 			of extensions.
	 * - Defaults to typical image file extensions (jpg, gif, jpeg, png)
	 *
	 * @see 	check_file_extension()
	 * @see 	check_allowed_file()
	 * @var		array|string	$exts
	 */
	var $exts = array( 'jpg', 'gif', 'jpeg', 'png' );

	/**
	 * Array of mime types
	 *
	 * The list is used to determine which files should be included in the result array if you want
	 * to strictly check that the files returned not only comply with the file extensions given, but
	 * are also of the expected mimetype.
	 * - Should be an array of mimetypes to show only files which comply with these mimetypes
	 * - High level mimetypes (content-types such as <i>image</i>) may be used and will be converted
	 * 			to an array which covers all mimetypes within that content-type.
	 * - If left empty and a strict check is done via the {@link check_allowed_file()} method, the
	 * 			relevant mimetypes will be guessed based on the passed extensions.
	 * - Defaults to typical image file extensions (jpg, gif, jpeg, png)
	 *
	 * @see 	check_file_mimetype()
	 * @see 	check_allowed_file()
	 * @see 	$exts
	 * @var		array	$mimetypes
	 */
	var $mimetypes = array( 'image/jpeg', 'image/png', 'image/gif' );

	/**
	 * Do a strict file type check ?
	 *
	 * Whether to check whether the files with the correct extension *really* are of the
	 * mime-type you would expect for a file with that extension.
	 * - Defaults to <i>false</i>, i.e. don't do a strict check
	 *
	 * @see		$mime_map
	 * @see 	$valid_mime_types
	 * @var 	boolean		$strict
	 */
	var $strict = false;

	/**
	 * Default format string for the human readable last modified and last access date
	 *
	 * - Refer to the {@link http://www.php.net/function.date php documentation} for information
	 * 			on formatting strings available.
	 * - If set to an empty string, the {@link get_human_readable_lastacc()} and
	 * 			{@link get_human_readable_lastmod()} methods will return an
	 * 			unformatted unix timestamp.
	 * - Defaults to <i>Y-m-d H:i:s</i> which should display something like <i>2006-08-31 00:00:00</i>
	 *
	 * @link	http://www.php.net/function.date
	 * @var		string	$date_time_format
	 **/
	var $datetime_format = 'Y-m-d H:i:s';


	/**
	 * Suffixes for use when creating human readable file size string
	 *
	 * @internal	ALWAYS (manually) adjust {@link $byte_suffix_count} if suffixes are
	 * 				added or deleted from this array
	 *
	 * @access 	private
	 * @var 	array	$byte_suffixes
	 **/
	var $byte_suffixes = array( 'b', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB' );

	/**
	 * Number of byte suffixes available
	 *
	 * @internal	ALWAYS (manually) adjust this if {@link $byte_suffixes} gets changed
	 *
	 * @access 	private
	 * @see 	$byte_suffixes
	 * @var 	int		$byte_suffix_count
	 **/
	var $byte_suffix_count = 9;


	/**********************************************************************
	 * INITIATION OF THE RESULT VARIABLES USED IN THE CLASS
	 *********************************************************************/

	/**
	 * Filelist result array
	 *
	 * @var		array	$filelist
	 **/
	var $filelist = array();

	/**
	 * Filelist selection result array
	 *
	 * @var		array	$filelist_selection
	 */
	var $filelist_selection = array();

	/**
	 * Directory result array
	 *
	 * @var		array	$dirlist
	 */
	var $dirlist = array();

	/**
	 * File count result variable
	 *
	 * Count of number of files in {@link $filelist}
	 * @var		int		$filecount
	 **/
	var $filecount = 0;

	/**
	 * File count of selection result variable
	 *
	 * Count of number of files in {@link $filelist_selection}
	 * @var		int		$fileselection_count
	 **/
	var $fileselection_count = 0;

	/**
	 * Directory count result variable
	 *
	 * Count of number of directories in {@link $dirlist}
	 * @var		int		$dircount
	 **/
	var $dircount = 0;


	/**
	 * Remember last path traversed for efficiency
	 *
	 * @see 	$pathtodir
	 * @var		string	$last_path
	 */
	var $last_path;

	/**
	 * Remember last recursive setting for efficiency
	 *
	 * - Default for recursion is <i>false</i>
	 *
	 * @var		string	$last_recursive
	 */
	var $last_recursive;

	/**
	 * Remember last passed extensions for efficiency
	 *
	 * @see 	$exts
	 * @var		array|string	$last_passed_exts
	 */
	var $last_passed_exts;

	/**
	 * Remember last validated extensions for efficiency
	 *
	 * @see 	$exts
	 * @var		array|string	$last_exts
	 */
	var $last_exts;

	/**
	 * Remember last passed mimetypes for efficiency
	 *
	 * @see 	$mimetypes
	 * @var		array	$last_passed_mimetypes
	 */
	var $last_passed_mimetypes;

	/**
	 * Remember last validated mimetypes for efficiency
	 *
	 * @see 	$mimetypes
	 * @var		array	$last_mimetypes
	 */
	var $last_mimetypes;


	/**********************************************************************
	 * METHODS TO CHANGE THE CLASS DEFAULTS
	 *********************************************************************/

	/**
	 * Change the {@link $pathtodir} default
	 *
	 * - Refer to {@link $pathtodir} for information on valid formats for the variable
	 * - Returns boolean true / false to indicate whether the default was changed succesfully
	 * @uses 	$pathtodir	to store new default
	 * @param	string	$pathtodir
	 * @return	bool
	 */
	function set_default_path( $pathtodir ) {
		if( ( is_string( $pathtodir ) && $pathtodir !== '' ) && is_dir( $pathtodir ) ) {
			$this->pathtodir = $pathtodir;
			return true;
		}
		return false;
	}

	/**
	 * Change the {@link $safe_exts} default
	 *
	 * - Refer to {@link $safe_exts} for information on valid formats for the variable
	 * - Returns boolean true / false to indicate whether the default was changed succesfully
	 * @uses 	$safe_exts	to store new default
	 * @param	bool	$safe_exts
	 * @return	bool
	 */
	function set_safe_exts( $safe_exts ) {
		if( is_bool( $safe_exts ) ) {
			$this->safe_exts = $safe_exts;
			return true;
		}
		return false;
	}

	/**
	 * Change the {@link $exts} default
	 *
	 * - Refer to {@link $exts} for information on valid formats for the variable
	 * - Returns boolean true / false to indicate whether the default was changed succesfully
	 * @uses  	$exts	to store new default
	 * @param	array|string	$exts
	 * @return	bool
	 */
	function set_default_exts( $exts ) {
		$exts = $this->validate_extension_list( $exts );
		if( $exts !== $this->exts ) {
			$this->exts = $exts;
			return true;
		}
		return false;

	}

	/**
	 * Change the {@link $mimetypes} default
	 *
	 * - Refer to {@link $mimetypes} for information on valid formats for the variable
	 * - Returns boolean true / false to indicate whether the default was changed succesfully
	 * @uses 	$mimetypes	to store new default
	 * @param	array	$mimetypes
	 * @return	bool
	 */
	function set_default_mimetypes( $mimetypes ) {
		$mimetypes = validate_mime_types( $mimetypes );
		if( $mimetypes !== $this->mimetypes ) {
			$this->mimetypes = $mimetypes;
			return true;
		}
		return false;
	}

	/**
	 * Change the {@link $strict} default
	 *
	 * - Refer to {@link $strict} for information on valid formats for the variable
	 * - Returns boolean true / false to indicate whether the default was changed succesfully
	 * @uses 	$strict		to store new default
	 * @param	bool	$strict
	 * @return	bool
	 */
	function set_strict( $strict ) {
		if( is_bool( $strict ) ) {
			$this->strict = $strict;
			return true;
		}
		return false;
	}

	/**
	 * Change the {@link $datetime_format} default
	 *
	 * - Refer to {@link $datetime_format} for information on valid formats for the variable
	 * - Returns boolean true / false to indicate whether the default was changed succesfully
	 * @uses 	$datetime_format	to store new default
	 * @param	string	$datetime_format
	 * @return	bool
	 */
	function set_datetime_format( $datetime_format ) {
		if( is_string( $datetime_format ) && $datetime_format !== '' ) {
			$this->datetime_format = $datetime_format;
			return true;
		}
		return false;
	}


	/**********************************************************************
	 * METHODS TO VALIDATE SOME PASSED PARAMETERS
	 *********************************************************************/

	/**
	 * Basic validation and parsing of a passed extensions parameter
	 *
	 * - This method does *not* check whether the extensions passed *are* real-life extensions,<br>
	 * 		i.e. it does not spell check nor check against a list of 'known' extensions.
	 * - If no parameter is passed or the parameter passed is not a string nor an array
	 * 		it defaults to the class default
	 * - If the passed $exts are the same as the last time this method was used, the check will
	 * 			be skipped and the results of last time will be used for efficiency
	 *
	 * @access 	private
	 * @uses 	$last_passed_exts	for efficiency check / sets the variable if the current
	 * 								passed $exts is different
	 * @uses 	$last_exts			for efficiency if the passed exts were already checked /
	 * 								sets this variable for same use if the passed $exts was
	 * 								different
	 * @uses 	$exts				to default to if no valid extensions parameter was passed
	 * @param	array|string	$exts	[optional] extensions parameter to validate
	 * @return	array|string	array of extensions or the string 'all'
	 */
	function validate_extension_list( $exts = null ) {

		if( $exts !== $this->last_passed_exts || is_null( $this->last_passed_exts ) ) {

			$this->last_passed_exts = $exts;

			// If it's a string, check for all, otherwise create an array containing 1 item
			if( is_string( $exts ) && $exts !== '' ) {
				if( strtolower( $exts ) === 'all' ) {
					$this->last_exts = strtolower( $exts );
				}
				else {
					$this->last_exts = ( array( strtolower( $exts ) ) );
				}
			}

			// If it's an array, make lowercase (which will cast the extension to string automatically)
			elseif( is_array( $exts ) && count( $exts ) > 0 ) {
				$ext_array = array();
				foreach( $exts as $ext ) {
					$ext_array[] = strtolower( $ext );
				}
				$this->last_exts = $ext_array;
			}

			// Otherwise return the default
			else {
				$this->last_exts = $this->exts;
			}
		}

		return $this->last_exts;
	}

	/**
	 * Validation and parsing of a passed mimetypes parameter
	 *
	 * - This method checks whether a passed array of mimetypes is valid against the official list of
	 * 			valid types
	 * - Invalid mimetypes will be removed from the array
	 * - If a string is passed, it will be turned into a 1-item array and validated as if it were an
	 * 			array
	 * - If an array item validates as a content-type mimetype, all subtypes for that content-type will
	 * 			be added to the array as valid mimetypes
	 * - If no mimetypes were passed or no valid mimetypes were found, the class default will be used
	 * - If the passed $mimetypes are the same as the last time this method was used, the check will
	 * 			be skipped and the results of last time will be used for efficiency
	 *
	 * @access 	private
	 * @uses 	$last_passed_mimetypes	for efficiency check / sets the variable if the current
	 * 									passed $mimetypes is different
	 * @uses 	$last_mimetypes		for efficiency if the passed mimetypes were already checked /
	 * 								sets this variable for same use if the passed $mimetypes was
	 * 								different
	 * @uses 	$mimetypes			to default to if no valid mimetypes were found
	 * @uses 	$valid_mime_types	to validate the passed mimetypes and to retrieve subtypes
	 * @param	array	$mimetypes	[optional] mimetypes to validate
	 * @return	array	validated mimetypes
	 */
	function validate_mime_types( $mimetypes = null ) {

		if( $mimetypes !== $this->last_passed_mimetypes || is_null( $this->last_passed_mimetypes ) ) {

			$this->last_passed_mimetypes = $mimetypes;

			if( is_string( $mimetypes ) && $mimetypes !== '' ) {
				// Cast to array and pass through
				$mimetypes = array( $mimetypes );
			}

			if( is_array( $mimetypes ) && count( $mimetypes ) > 0 ) {

				$mime_array = array();

				foreach( $mimetypes as $mimetype ) {

					if( is_string( $mimetype ) && $mimetype !== '' ) {
						if( strpos( $mimetype, '/') === false ) {
							if( isset( $this->valid_mime_types[$mimetype] ) ) {
								foreach( $this->valid_mime_types[$mimetype] as $subtype ) {
									$mime_array[] = $mimetype . '/' . $subtype;
								}
							}
						}
						else {
							$mimeparts = explode( '/', $mimetype, 2 );
							if( in_array( $mimeparts[1], $this->valid_mime_types[$mimeparts[0]], true) ) {
								$mime_array[] = $mimetype;
							}
						}
					}
				}

				if( count( $mime_array ) > 0 ) {
					$this->last_mimetypes = array_unique( $mime_array );
				}
			}
			else {
				$this->last_mimetypes = $this->mimetypes;
			}
		}

		return $this->last_mimetypes;
	}

	/**
	 * Clears the file stat cache and checks whether the passed $pathtofile is a valid path to a file
	 *
	 * @param	string $pathtofile
	 * @return	bool
	 */
	function valid_pathtofile( $pathtofile ) {
		clearstatcache();

		// Check if a non empty string has been passed as pathtofile
		return ( ( is_string( $pathtofile ) && $pathtofile !== '' ) && file_exists( $pathtofile ) );
	}


	/**********************************************************************
	 * METHODS TO RETRIEVE INFORMATION ON INDIVIDUAL FILES
	 *********************************************************************/

	/**
	 * Check whether the file is allowed based on extension and if $strict=true also on mimetype
	 *
	 * - Will use the class defaults for optional parameters which were not passed
	 * - Will try to 'guess' the mimetypes if a strict check is requested, but no mimetypes were
	 * 			passed
	 * - Returns <i>true</i> is the file passes, <i>false</i> if not.
	 *
	 * @see 	$exts
	 * @see 	$strict
	 * @see 	$mimetypes
	 * @uses 	$mime_map		to 'guess' mimetypes based on $exts if no $mimetypes parameter
	 * 							was passed and a strict check was requested
	 * @uses 	validate_extension_list()	to validate the passed extension list
	 * @uses 	check_file_extension()		to validate the file against the extension list
	 * @uses 	check_file_mimetype()		to validate the file against the mimetypes
	 * @param	string			$pathtofile		path to the file to check
	 * @param	array|string	$exts			[optional] allowed extensions
	 * @param	bool			$strict			[optional] whether or not to check on mimetype
	 * @param	array			$mimetypes		[optional] allowed mimetypes
	 * @return	bool
	 */
	function check_allowed_file( $pathtofile, $exts = null, $strict = null, $mimetypes = null ) {

		$strict = ( is_bool( $strict ) ) ? $strict : $this->strict;

		if( $strict ) {

			/**
			 * We only try to build a mimetype list based on the extensions when exts is not null,
			 * not all nor the default and mimetypes is null.
			 * In all other cases this is not needed as the {@see check_file_extension()}
			 * and the {@see check_file_mimetype()} will validate the passed parameters anyway
			 * and will default to the class default.
			 */
			if( is_null( $mimetypes ) && !is_null( $exts ) ) {
				$exts = $this->validate_extension_list( $exts );

				if( is_string( $exts) && strtolower( $exts ) === 'all' ) {
					// Fall through - mimetype check superfluous
					return ( $this->check_file_extension( $pathtofile, $exts ) );
				}
				elseif( $exts === $this->exts && !is_null( $this->mimetypes ) ) {
					$mimetypes = $this->mimetypes;
				}
				else {
					$exts = $this->validate_extension_list( $exts );
					$mimetypes = array();
					foreach( $exts as $ext ) {
						if( isset( $this->mime_map[$ext] ) ) {
							$mimetypes[] = $this->mime_map[$ext];
						}
						else {
							trigger_error( 'The file extension <em>' . $ext . '</em> does not have a valid mime-type associated with it in the mime_map', E_USER_WARNING );
						}
					}
					$mimetypes = array_unique( $mimetypes );
				}
			}
			return ( $this->check_file_extension( $pathtofile, $exts ) && $this->check_file_mimetype( $pathtofile, $mimetypes ) );
		}
		else {
			return ( $this->check_file_extension( $pathtofile, $exts ) );
		}
	}

	/**
	 * Check the file extension of a filename against the list of allowed extensions
	 *
	 * - This is a case-insensitive extension check
	 * - Will use the class defaults for optional parameters which were not passed
	 * - Returns <i>true</i> if the filename passes the valid extension check
	 * - Returns <i>false</i> is it fails or if the passed filename parameter is not a string
	 *
	 * @see 	$exts
	 * @uses 	validate_extension_list()	used to parse the extension list to the expected format
	 * @param	string			$filename	filename to check
	 * @param 	array|string	$exts		[optional] array of allowed extensions
	 * @return	bool
	 **/
	function check_file_extension( $filename, $exts = null ) {

		// Check if a non empty string has been passed as filename
		if( !is_string( $filename ) || $filename === '' ) {
			return false;
		}

		// Validate the optional parameters and default to the class defaults if not passed or invalid
		$exts = $this->validate_extension_list( $exts );

		// If all extensions are allowed, return true
		if( $exts === 'all') {
			return true;
		}

		// If the function is still running, check the extension against the allowed extension list
		$pos = strrpos( $filename, '.' );
		if( $pos !== false ) {
			// Strip the everything before and including the '.'
			$file_ext = substr( $filename, ( $pos + 1 ) );
			return( in_array( $file_ext, $exts, true ) );
		}

		// No extension found
		return false;
	}

	/**
	 * Check the file-mimetype against a list of allowed mimetypes
	 *
	 * - Will use the class defaults for optional parameters which were not passed
	 * - Returns <i>true</i> if the file-mimetype is within the list of allowed mimetypes
	 * - Returns <i>false</i> if not or if the passed filename parameter is not a string
	 *
	 * @see 	$mimetypes
	 * @uses 	valid_pathtofile()		to check whether the $pathtofile parameter is valid
	 * @uses 	validate_mime_types()	to validate the passed mimetypes
	 * @uses 	get_mime_content_type()	to retrieve the file mimetype
	 * @param	string		$pathtofile
	 * @param	array		$mimetypes	[optional] array of valid mimetypes
	 * @return	bool
	 */
	function check_file_mimetype( $pathtofile, $mimetypes = null ) {
		if( !$this->valid_pathtofile( $pathtofile ) ) {
			return false;
		}
		$mimetypes = $this->validate_mime_types( $mimetypes );
		$file_mimetype = $this->get_mime_content_type( $pathtofile );
		return ( in_array( $file_mimetype, $mimetypes ) );
	}


	/**
	 * Get the filesize of a file
	 *
	 * @static
	 * @link 	http://www.php.net/function.filesize
	 * @uses 	valid_pathtofile()	to check whether the passed parameter is a file
	 * @param	string		$pathtofile
	 * @return	int|false	filesize or false if an invalid $pathtofile was passed
	 */
	function get_filesize( $pathtofile ) {
		if( !directory_info::valid_pathtofile( $pathtofile ) ) {
			return false;
		}
		return filesize( $pathtofile );
	}

	/**
	 * Get the filesize of a file in a human readable format
	 *
	 * @uses 	get_filesize()	to retrieve the filesize
	 * @uses 	human_readable_filesize()	to convert the filesize to a human readable string
	 * @param	string			$pathtofile
	 * @return	string|false	human readable filesize string or false if an invalid
	 * 							$pathtofile was passed
	 */
	function get_human_readable_filesize( $pathtofile ) {
		$filesize = $this->get_filesize( $pathtofile );
		return ( ( $filesize !== false ) ? $this->human_readable_filesize( $filesize ) : false );
	}

	/**
	 * Creates a human readable file size string
	 *
	 * - Rounds bytes and kilobytes to the nearest integer
	 * - Rounds anything else to one digit behind the decimal point
	 * - Returns <i>false</i> is the passed parameter is not an integer or a numeric string
	 *
	 * Examples:<br>
	 * the integer <i>1080</i> becomes the string <i>1 kB</i><br>
	 * the integer <i>3000000</i> becomes the string <i>2.8 MB</i>
	 *
	 * @uses 	$byte_suffixes		for the byte suffixes
	 * @uses 	$byte_suffix_count
	 * @param	int				$filesize	filesize in bytes
	 * @return	string|false	human readable filesize string
	 * 							or false if the passed variable was not an integer
	 **/
	function human_readable_filesize( $filesize ) {

		if( is_int( $filesize ) && $filesize > 0 ) {

			// Get the figure to use in the string
			for( $i = 0; ( $i < $this->byte_suffix_count && $filesize >= 1024 ); $i++ ) {
				$filesize = $filesize / 1024;
			}

			// Return the rounded figure with the appropriate suffix
			if( $this->byte_suffixes[$i] === 'b' || $this->byte_suffixes[$i] === 'kB' ) {
				return( round( $filesize, 0 ) . ' ' . $this->byte_suffixes[$i] );
			}
			else {
				return( round( $filesize, 1 ) . ' ' . $this->byte_suffixes[$i] );
			}
		}
		else {
			return false;
		}
	}


	/**
	 * Get the last modified unix timestamp for a file
	 *
	 * @static
	 * @link 	http://www.php.net/function.filemtime
	 * @uses 	valid_pathtofile()	to check whether the passed parameter is a file
	 * @param	string		$pathtofile
	 * @return	int|false	unix timestamp or false if an invalid $pathtofile was passed
	 */
	function get_lastmod_unixts( $pathtofile ) {
		if( !directory_info::valid_pathtofile( $pathtofile ) ) {
			return false;
		}
		return filemtime( $pathtofile );
	}

	/**
	 * Get the last modified timestamp of a file in a human readable format
	 *
	 * @uses 	get_lastmod_unixts()	to retrieve the last modified unix timestamp
	 * @uses 	$datetime_format		as a default date/time format if no format was passed
	 * @param	string			$pathtofile
	 * @param 	string			$datetime_format	[optional]
	 * @return	string|false	human readable date/time string or false if an invalid
	 * 							$pathtofile was passed
	 */
	function get_human_readable_lastmod( $pathtofile, $datetime_format = null ) {
		if( !is_string( $datetime_format ) || $datetime_format === '' ) {
			$datetime_format = $this->datetime_format;
		}
		$uts = $this->get_lastmod_unixts( $pathtofile );
		return ( ( $datetime_format !== '' && $uts !== false ) ? date( $datetime_format, $uts ) : false );
	}

	/**
	 * Get the last access unix timestamp for a file
	 *
	 * @static
	 * @link 	http://www.php.net/function.fileatime
	 * @uses 	valid_pathtofile()	to check whether the passed parameter is a file
	 * @param	string		$pathtofile
	 * @return	int|false	unix timestamp or false if an invalid $pathtofile was passed
	 */
	function get_lastacc_unixts( $pathtofile ) {
		if( !directory_info::valid_pathtofile( $pathtofile ) ) {
			return false;
		}
		return fileatime( $pathtofile );
	}

	/**
	 * Get the last access timestamp of a file in a human readable format
	 *
	 * @uses 	get_lastacc_unixts()	to retrieve the last access unix timestamp
	 * @uses 	$datetime_format		as a default date/time format if no format was passed
	 * @param	string			$pathtofile
	 * @param 	string			$datetime_format	[optional]
	 * @return	string|false	human readable date/time string or false if an invalid
	 * 							$pathtofile was passed
	 */
	function get_human_readable_lastacc( $pathtofile, $datetime_format = null ) {
		if( !is_string( $datetime_format ) || $datetime_format === '' ) {
			$datetime_format = $this->datetime_format;
		}
		$uts = $this->get_lastacc_unixts( $pathtofile );
		return ( ( $datetime_format !== '' && $uts !== false ) ? date( $datetime_format, $uts ) : false );
	}

	/**
	 * Get the file owner for a file
	 *
	 * @static
	 * @link 	http://www.php.net/function.fileowner
	 * @uses 	valid_pathtofile()	to check whether the passed parameter is a file
	 * @param	string		$pathtofile
	 * @return	int|false	user id of the file owner or false if an invalid $pathtofile was passed
	 */
	function get_file_owner( $pathtofile ) {
		if( !directory_info::valid_pathtofile( $pathtofile ) ) {
			return false;
		}
		return fileowner( $pathtofile );
	}

	/**
	 * Get the mime content type of a file
	 *
	 * @static
	 * @author	keczerad at poczta dot fm - 30-Aug-2006 10:38
	 * @link 	http://www.php.net/function.mime-content-type
	 * @uses 	valid_pathtofile()	to check whether the passed parameter is a file
	 * @param	string			$pathtofile
	 * @return	string|false	mimetype string or false if an invalid $pathtofile was passed
	 */
	function get_mime_content_type( $pathtofile ) {
		if( !directory_info::valid_pathtofile( $pathtofile ) ) {
			return false;
		}

		if( function_exists( 'mime_content_type' ) ) {
			return mime_content_type( $pathtofile );
		}
		else {
			return exec( trim( 'file -bi ' . escapeshellarg( $pathtofile ) ) ) ;
		}
	}

	/**
	 * Get a human readable file permission string for a file
	 *
	 * @static
	 * @link 	http://www.php.net/function.fileperms
	 * @uses 	valid_pathtofile()	to check whether the passed parameter is a file
	 * @param	string			$pathtofile
	 * @return	string|false	file permission string or false if an invalid $pathtofile was passed
	 */
	function get_human_readable_file_permissions( $pathtofile ) {

		if( !directory_info::valid_pathtofile( $pathtofile ) ) {
			return false;
		}

		$perms = fileperms( $pathtofile );

		if( ( $perms & 0xC000 ) == 0xC000 ) { $info = 's'; } // Socket
		elseif( ( $perms & 0xA000 ) == 0xA000 ) { $info = 'l'; } // Symbolic Link
		elseif( ( $perms & 0x8000 ) == 0x8000 ) { $info = '-'; } // Regular
		elseif( ( $perms & 0x6000 ) == 0x6000 ) { $info = 'b'; } // Block special
		elseif( ( $perms & 0x4000 ) == 0x4000 ) { $info = 'd'; } // Directory
		elseif( ( $perms & 0x2000 ) == 0x2000 ) { $info = 'c'; } // Character special
		elseif( ( $perms & 0x1000 ) == 0x1000 ) { $info = 'p'; } // FIFO pipe
		else { $info = 'u';	} // Unknown

		// Owner
		$info .= ( ( $perms & 0x0100 ) ? 'r' : '-' );
		$info .= ( ( $perms & 0x0080 ) ? 'w' : '-' );
		$info .= ( ( $perms & 0x0040 ) ?
					( ( $perms & 0x0800 ) ? 's' : 'x' ) :
           			( ( $perms & 0x0800 ) ? 'S' : '-' ) );

		// Group
		$info .= ( ( $perms & 0x0020 ) ? 'r' : '-' );
		$info .= ( ( $perms & 0x0010 ) ? 'w' : '-' );
		$info .= ( ( $perms & 0x0008 ) ?
					( ( $perms & 0x0400 ) ? 's' : 'x' ) :
					( ( $perms & 0x0400 ) ? 'S' : '-' ) );

		// World
		$info .= ( ( $perms & 0x0004 ) ? 'r' : '-' );
		$info .= ( ( $perms & 0x0002 ) ? 'w' : '-' );
		$info .= ( ( $perms & 0x0001 ) ?
					( ( $perms & 0x0200 ) ? 't' : 'x' ) :
					( ( $perms & 0x0200 ) ? 'T' : '-' ) );

		return $info;
	}


	/**********************************************************************
	 * METHODS TO RETRIEVE FILE LISTS
	 *********************************************************************/

	/**
	 * Get list of files in $pathtodir
	 *
	 * - Use this method to retrieve a filelist for a certain directory path
	 * - If a filelist was created before and you want to retrieve this list, you can use this function
	 * 		without any parameters and it will return the previously created list
	 * - If you created a selection based on the earlier created filelist, you can choose to retrieve
	 * 		that selection instead by setting $use_selection to <i>true</i>
	 * - If no filelist was created before and no parameters are passed, it will retrieve a filelist
	 * 		based on the class defaults
	 * - If you call this method as a static method, logically you can not retrieve an earlier
	 * 		created listing or selection list
	 *
	 * @uses 	$last_path		to check whether the requested list already exists
	 * @uses 	$last_recursive	to check whether the requested list already exists
	 * @uses 	traverse_directory()	to retrieve a new filelist if needed
	 * @uses 	$filelist		to return the list as stored by {@link traverse_directory}
	 * 							now or earlier
	 * @uses 	$filecount		to check that $filelist contains results if the requested list
	 * 							already seemed to exist
	 * @uses 	$pathtodir		to default to if no $pathtodir was passed
	 * @uses 	$fileselection_count	to check that $filelist_selection contains results if the
	 * 							requested list already seemed to exist and the last selection was
	 * 							requested - if selection was empty, then the complete list will be
	 * 							returned
	 * @uses 	$filelist_selection		to return the selection list if the selection list was
	 * 							not empty and the selection was requested
	 *
	 * @param	bool	$use_selection	[optional]	whether or not the last made selection should
	 * 									be returned if available
	 * @param	string	$pathtodir		[optional]	path to the directory for which to get the list
	 * @param	bool	$recursive		[optional]	whether to retrieve information on files in
	 * 									subdirectories
	 * @return	array	array of filenames
	 */
	function get_filelist( $use_selection = null, $pathtodir = null, $recursive = null ) {

		// If a pathtodir was passed and the path to dir was not the same as the last one used
		// to get a filelist, build a new filelist
		if( !is_null( $pathtodir ) && ( $pathtodir !== $this->last_path || $recursive !== $this->last_recursive ) ) {
			$this->traverse_directory( $pathtodir, $recursive );
			return $this->filelist;
		}
		elseif( is_null( $pathtodir ) && $this->filecount === 0 ) {
			$this->traverse_directory( $this->pathtodir, $recursive );
			return $this->filelist;
		}
		elseif( $use_selection === true && $this->fileselection_count > 0 ) {
			return $this->filelist_selection;
		}
		else {
			return $this->filelist;
		}
	}

	/**
	 * Get a list of directories in $pathtodir
	 *
	 * @see 	get_filelist()	for more information on retrieving an earlier created list
	 * @uses 	$last_path		to check whether the requested list already exists
	 * @uses 	$last_recursive	to check whether the requested list already exists
	 * @uses 	traverse_directory()	to retrieve a new dirlist if needed
	 * @uses 	$dirlist		to return the list as stored by {@link traverse_directory}
	 * 							now or earlier
	 * @param	string	$pathtodir		[optional]	path to the directory for which to get the list
	 * @param	bool	$recursive		[optional]	whether to retrieve information on directories in
	 * 									subdirectories
	 * @return	array	array of filenames
	 */
	function get_dir_list( $pathtodir, $recursive = null ) {

		if( !is_null( $pathtodir ) && ( $pathtodir !== $this->last_path || $recursive !== $this->last_recursive ) ) {
			$this->traverse_directory( $pathtodir, $recursive );
		}
		elseif( is_null( $pathtodir ) && $this->dircount === 0 ) {
			$this->traverse_directory( $this->pathtodir, $recursive );
		}
		return $this->dirlist;
	}

	/**
	 * Function to traverse a directory
	 *
	 * - This is the actual workhorse method which traverses the directory
	 * - This method checks whether something is a file before accepting the file in the filelist
	 * - This method uses the {@link $safe_exts} class setting to determine whether or not to include
	 * 		'unsafe' files
	 * - This function can be called recursively for subdirectories, but this has to be explicitely set
	 * 		Default is non-recursive
	 * - The results of the function are stored in the class variables {@link $filelist},
	 * 		{@link $filecount}, {@link $dirlist} and {@link $dircount}
	 *
	 * @access	private
	 * @link 	http://www.php.net/function.readdir
	 * @uses 	$last_path				sets this variable
	 * @uses 	$last_recursive			sets this variable
	 * @uses 	$filelist_selection		re-sets this variable
	 * @uses 	$fileselection_count	re-sets this variable
	 * @uses 	$filelist				to store the results
	 * 									(sorted ascendingly in case-insensitive natural sort order)
	 * @uses 	$filecount				to store a count of $filelist
	 * @uses 	$dirlist				to store the results
	 * 									(sorted ascendingly in case-insensitive natural sort order)
	 * @uses 	$dircount				to store a count of $dirlist
	 *
	 * @param	string	$pathtodir	[optional]	path to the directory for which to get the list
	 * @param	bool	$recursive	[optional]	whether to retrieve information on files in
	 * 								subdirectories
	 * @param	string	$prefix		[optional] gets set internally when this function
	 * 								is used recursively
	 * @return 	void	sets class variables {@link $filelist} and {@link $filecount}
	 *
	 */
	function traverse_directory( $pathtodir, $recursive = false, $prefix = '' ) {

		if( $prefix === '' ) {
			$this->last_path = $pathtodir;
			$this->last_recursive = $recursive;
			$this->filelist = array();
			$this->filelist_selection = array();
			$this->dirlist = array();
			$this->filecount = 0;
			$this->fileselection_count = 0;
			$this->dircount = 0;
		}

		if( $handle = @opendir( $pathtodir ) ) {

			while( ( $filename = readdir( $handle ) ) !== false ) {


				// Check if the file is an 'unsafe' one such as .htaccess or
				// higher directory references, if so, skip
				if( $this->safe_exts === true && strpos( $filename, '.' ) === 0 ) {
					// do nothing
				}
				else {
					// If it's a file, check against valid extensions and add to the list
					if( is_file( $pathtodir . '\\' . $filename ) === true ) {
						$this->filelist[] = $prefix . $filename;
					} 

					// If it's a directory and subdirectories should be listed,
					// add the subdirectory to the list.
					// If files from subdirs should be listed, run this function on the subdirectory
					elseif( is_dir( $pathtodir . $filename ) === true ) {
						$this->dirlist[] = $prefix . $filename . '/';
						if( $recursive === true) {
							$this->traverse_directory( $pathtodir . $filename . '/', $recursive, $prefix . $filename . '/' );
						}
					}
				}
				unset( $filename );
			}
			closedir( $handle );

			$this->filecount = count( $this->filelist );
			$this->dircount = count( $this->dirlist );


			if( $this->dircount > 1 ) {
				natcasesort( $this->dirlist );
				$this->dirlist = array_values( $this->dirlist );
			}
			if( $this->filecount > 1 ) {
				natcasesort( $this->filelist );
				$this->filelist = array_values( $this->filelist );
			}
		}
	}


	/**
	 * Retrieve a filelist which only contains files which comply with the allowed extension/mimetypes
	 *
	 * - Creates a selection list of files which comply with the criteria set by
	 * 		allowed extensions / allowed mimetypes
	 * - $strict determined whether or not to check on mimetype
	 * - $exts, $strict and $mimetypes default to the class defaults if not passed
	 *
	 * @uses 	get_filelist()	to retrieve the requested filelist - refer to this method for
	 * 									more information on retrieving listings already created
	 * @uses 	check_allowed_file()	to check for each file whether it complies with the criteria
	 * @uses 	$filelist_selection		to store the results
	 * @uses 	$fileselection_count	to store a count of the results
	 *
	 * @param	bool	$use_selection	[optional]	whether or not the last made selection should
	 * 									be returned if available
	 * @param	string	$pathtodir		[optional]	path to the directory for which to get the list
	 * @param	bool	$recursive		[optional]	whether to retrieve information on files in
	 * 									subdirectories
	 * @param	array|string	$exts			[optional] allowed extensions
	 * @param	bool			$strict			[optional] whether or not to check on mimetype
	 * @param	array			$mimetypes		[optional] allowed mimetypes
	 * @return	array			array of filenames of files which pass the test
	 */
	function get_ext_based_filelist( $use_selection = null, $pathtodir = null, $recursive = null, $exts = null, $strict = null, $mimetypes = null ) {

		$files = $this->get_filelist( $use_selection, $pathtodir, $recursive );

		$passed_files = array();

		foreach( $files as $filename ) {
			if( $this->check_allowed_file( $this->last_path . $filename, $exts, $strict, $mimetypes ) ) {
				$passed_files[] = $filename;
			}
		}

		$this->filelist_selection = $passed_files;
		$this->fileselection_count = count( $this->filelist_selection );
		return $this->filelist_selection;
	}


	/**
	 * Retrieve a sorted filelist
	 *
	 * - Mainly useful for reverse sorting as the normal filelist is already sorted in
	 * 		ascending order
	 * - Defaults to <i>ascending</i> sort order
	 * - To sort descendingly, set $sort_asc to <i>false</i>
	 * - The list sorting will always use case-insensitive natural sort order
	 * - Retrieving a sorted list will not affect the order of the class 'remembered' filelists
	 *
	 * @link 	http://www.php.net/function.natcasesort
	 * @uses 	get_filelist()	to retrieve the requested filelist - refer to this method for
	 * 									more information on retrieving listings already created
	 * @param	bool	$sort_asc		[optional]	set to false for reverse / descending sorted list
	 * @param	bool	$use_selection	[optional]	whether or not the last made selection should
	 * 									be returned if available
	 * @param	string	$pathtodir		[optional]	path to the directory for which to get the list
	 * @param	bool	$recursive		[optional]	whether to retrieve information on files in
	 * 									subdirectories
	 * @return	array	sorted array of filenames
	 */
	function get_sorted_filelist( $sort_asc = null, $use_selection = null, $pathtodir = null, $recursive = null ) {

		$files = $this->get_filelist( $use_selection, $pathtodir, $recursive );

		// Sort the resulting file list
		if( count( $files ) > 1 ) {
			natcasesort( $files );
			if( $sort_asc === false ) {
				$files = array_reverse( $files, true );
			}
			$files = array_values( $files );
		}
		return $files;
	}

	/**
	 * Retrieve a sorted (sub-)directory list
	 *
	 * - Mainly useful for reverse sorting as the normal dirlist is already sorted in
	 * 		ascending order
	 * - Defaults to <i>ascending</i> sort order
	 * - To sort descendingly, set $sort_asc to <i>false</i>
	 * - The list sorting will always use case-insensitive natural sort order
	 * - Retrieving a sorted list will not affect the order of the class 'remembered' dirlist
	 *
	 * @link 	http://www.php.net/function.natcasesort
	 * @see 	get_filelist()	for more information on retrieving an earlier created list
	 * @uses 	get_dir_list()	to retrieve the directory list
	 * @param	bool	$sort_asc		[optional]	set to false for reverse / descending sorted list
	 * @param	string	$pathtodir		[optional]	path to the directory for which to get the list
	 * @param	bool	$recursive		[optional]	whether to retrieve information on directories in
	 * 									subdirectories
	 * @return	array	sorted array of (sub-)directory names
	 */
	function get_sorted_dirlist( $sort_asc = null, $pathtodir = null, $recursive = null ) {

		$dirs = $this->get_dir_list( $pathtodir, $recursive );

		// Sort the resulting file list
		if( count( $dirs ) > 1 ) {
			natcasesort( $dirs );
			if( $sort_asc === false ) {
				$dirs = array_reverse( $dirs, true );
			}
			$dirs = array_values( $dirs );
		}
		return $dirs;
	}

	/**
	 * Retrieve the filename and last_modified date of the most recently modified file
	 *
	 * Inspired by a comment from wookie at at no-way dot org - 14-Sep-2003 11:17
	 *
	 * @link 	http://www.php.net/function.filemtime
	 * @uses 	get_filelist()	to retrieve the requested filelist - refer to this method for
	 * 									more information on retrieving listings already created
	 * @uses 	get_lastmod_unixts()	to get the last modified unix timestamp for each file to test
	 * @param	bool	$use_selection	[optional]	whether or not the last made selection should
	 * 									be used if available
	 * @param	string	$pathtodir		[optional]	path to the directory for which to get the list
	 * @param	bool	$recursive		[optional]	whether to retrieve information on files in
	 * 									subdirectories
	 * @return	array	array with two key-value sets:
	 * 					'filename' => filename of most recent file
	 * 					'last_modified'	=> last modified unix timestamp of the file
	 */
	function get_most_recent_file( $use_selection = null, $pathtodir = null, $recursive = null ) {

		$files = $this->get_filelist( $use_selection, $pathtodir, $recursive );

		// Initialize result
		$last_mod_ts = 0;
		$last_mod_file = '';

		foreach( $files as $filename ) {
			$file_mod_ts = $this->get_lastmod_unixts( $this->last_path . $filename);
			if( $file_mod_ts > $last_mod_ts ) {
				$last_mod_ts = $file_mod_ts;
				$last_mod_file = $filename;
			}
			unset( $file_mod_ts );
		}

		return array( 'filename' => $last_mod_file, 'last_modified' => $last_mod_ts );
	}


	/**
	 * Retrieve a filelist which only contains files modified since the passed unix timestamp
	 *
	 * - Creates a selection list of files which comply with the criteria set by $comparets
	 *
	 * Inspired by a comment from Benan Tumkaya (benantumkaya at yahoo) - 14-Aug-2006 11:11
	 *
	 * @link 	http://www.php.net/function.filemtime
	 * @uses 	get_filelist()	to retrieve the requested filelist - refer to this method for
	 * 									more information on retrieving listings already created
	 * @uses 	get_lastmod_unixts()	to get the last modified unix timestamp for each file to test
	 * @uses 	$filelist_selection		to store the results
	 * @uses 	$fileselection_count	to store a count of the results
	 *
	 * @param	int		$compare_ts		Unix timestamp for date/time to compare against
	 * @param	bool	$use_selection	[optional]	whether or not the last made selection should
	 * 									be used if available
	 * @param	string	$pathtodir		[optional]	path to the directory for which to get the list
	 * @param	bool	$recursive		[optional]	whether to retrieve information on files in
	 * 									subdirectories
	 * @return	array	array of filenames of files which pass the test
	 */
	function get_files_modified_since( $compare_ts, $use_selection = null, $pathtodir = null, $recursive = null ) {

		$files = $this->get_filelist( $use_selection, $pathtodir, $recursive );

		foreach( $files as $key => $filename ) {
			$file_mod_ts = $this->get_lastmod_unixts( $this->last_path . $filename);
			if( $file_mod_ts < $compare_ts ) {
				unset( $files[$key] );
			}
		}

		$this->filelist_selection = $files;
		$this->fileselection_count = count( $this->filelist_selection );
		return $this->filelist_selection;
	}

	/**
	 * Retrieve a filelist which only contains files modified before the passed unix timestamp
	 *
	 * - Creates a selection list of files which comply with the criteria set by $comparets
	 *
	 * Inspired by a comment from Benan Tumkaya (benantumkaya at yahoo) - 14-Aug-2006 11:11
	 *
	 * @link 	http://www.php.net/function.filemtime
	 * @uses 	get_filelist()	to retrieve the requested filelist - refer to this method for
	 * 									more information on retrieving listings already created
	 * @uses 	get_lastmod_unixts()	to get the last modified unix timestamp for each file to test
	 * @uses 	$filelist_selection		to store the results
	 * @uses 	$fileselection_count	to store a count of the results
	 *
	 * @param	int		$compare_ts		Unix timestamp for date/time to compare against
	 * @param	bool	$use_selection	[optional]	whether or not the last made selection should
	 * 									be used if available
	 * @param	string	$pathtodir		[optional]	path to the directory for which to get the list
	 * @param	bool	$recursive		[optional]	whether to retrieve information on files in
	 * 									subdirectories
	 * @return	array	array of filenames of files which pass the test
	 */
	function get_files_modified_before( $compare_ts, $use_selection = null, $pathtodir = null, $recursive = null ) {

		$files = $this->get_filelist( $use_selection, $pathtodir, $recursive );

		foreach( $files as $key => $filename ) {
			$file_mod_ts = $this->get_lastmod_unixts( $this->last_path . $filename);
			if( $file_mod_ts > $compare_ts ) {
				unset( $files[$key] );
			}
		}

		$this->filelist_selection = $files;
		$this->fileselection_count = count( $this->filelist_selection );
		return $this->filelist_selection;
	}

	/**
	 * Retrieve a filelist which only contains files accessed since the passed unix timestamp
	 *
	 * - Creates a selection list of files which comply with the criteria set by $comparets
	 *
	 * Inspired by a comment from Benan Tumkaya (benantumkaya at yahoo) - 14-Aug-2006 11:11
	 *
	 * @link 	http://www.php.net/function.fileatime
	 * @uses 	get_filelist()	to retrieve the requested filelist - refer to this method for
	 * 									more information on retrieving listings already created
	 * @uses 	get_lastacc_unixts()	to get the last access unix timestamp for each file to test
	 * @uses 	$filelist_selection		to store the results
	 * @uses 	$fileselection_count	to store a count of the results
	 *
	 * @param	int		$compare_ts		Unix timestamp for date/time to compare against
	 * @param	bool	$use_selection	[optional]	whether or not the last made selection should
	 * 									be used if available
	 * @param	string	$pathtodir		[optional]	path to the directory for which to get the list
	 * @param	bool	$recursive		[optional]	whether to retrieve information on files in
	 * 									subdirectories
	 * @return	array	array of filenames of files which pass the test
	 */
	function get_files_accessed_since( $compare_ts, $use_selection = null, $pathtodir = null, $recursive = null ) {

		$files = $this->get_filelist( $use_selection, $pathtodir, $recursive );

		foreach( $files as $key => $filename ) {
			$file_mod_ts = $this->get_lastacc_unixts( $this->last_path . $filename);
			if( $file_mod_ts < $comparets ) {
				unset( $files[$key] );
			}
		}

		$this->filelist_selection = $files;
		$this->fileselection_count = count( $this->filelist_selection );
		return $this->filelist_selection;
	}

	/**
	 * Retrieve a filelist which only contains files accessed before the passed unix timestamp
	 *
	 * - Creates a selection list of files which comply with the criteria set by $comparets
	 *
	 * Inspired by a comment from Benan Tumkaya (benantumkaya at yahoo) - 14-Aug-2006 11:11
	 *
	 * @link 	http://www.php.net/function.fileatime
	 * @uses 	get_filelist()	to retrieve the requested filelist - refer to this method for
	 * 									more information on retrieving listings already created
	 * @uses 	get_lastacc_unixts()	to get the last access unix timestamp for each file to test
	 * @uses 	$filelist_selection		to store the results
	 * @uses 	$fileselection_count	to store a count of the results
	 *
	 * @param	int		$compare_ts		Unix timestamp for date/time to compare against
	 * @param	bool	$use_selection	[optional]	whether or not the last made selection should
	 * 									be used if available
	 * @param	string	$pathtodir		[optional]	path to the directory for which to get the list
	 * @param	bool	$recursive		[optional]	whether to retrieve information on files in
	 * 									subdirectories
	 * @return	array	array of filenames of files which pass the test
	 */
	function get_files_accessed_before( $compare_ts, $use_selection = null, $pathtodir = null, $recursive = null ) {

		$files = $this->get_filelist( $use_selection, $pathtodir, $recursive );

		foreach( $files as $key => $filename ) {
			$file_mod_ts = $this->get_lastacc_unixts( $this->last_path . $filename);
			if( $file_mod_ts > $comparets ) {
				unset( $files[$key] );
			}
		}

		$this->filelist_selection = $files;
		$this->fileselection_count = count( $this->filelist_selection );
		return $this->filelist_selection;
	}


	/**
	 * Get the total size of all files in $pathtodir
	 *
	 * @author	marting.dc AT gmail.com - 29-Jan-2006 02:08
	 * @see		http://www.php.net/function.stat
	 * @uses 	get_filelist()	to retrieve a filelist to use - refer to this method for
	 * 									more information on retrieving listings already created
	 * @uses 	get_filesize()			to retrieve information on the filesize of individual files
	 *
	 * @param	bool	$use_selection	[optional]	whether or not the last made selection should
	 * 									be used if available
	 * @param	string	$pathtodir		[optional]	path to the directory for which to get the size
	 * @param	bool	$recursive		[optional]	whether to include filesize of files in
	 * 									subdirectories
	 * @return	int		total size of files in directory in bytes
	 */
	function get_dirsize( $use_selection = null, $pathtodir = null, $recursive = null ) {

		$files = $this->get_filelist( $use_selection, $pathtodir, $recursive );

		// Initialize result
		$dirsize = 0;

		foreach( $files as $filename ) {
			$dirsize += $this->get_filesize( $this->last_path . $filename );
		}

		return $dirsize;
	}

	/**
	 * Get the total size of all files in a directory in a human readable format
	 *
	 * @uses 	get_dirsize()	to retrieve the total size of the files in the directory
	 * @uses 	human_readable_filesize()	to convert the size to a human readable string
	 * @param	bool	$use_selection	[optional]	whether or not the last made selection should
	 * 									be used if available
	 * @param	string	$pathtodir		[optional]	path to the directory for which to get the size
	 * @param	bool	$recursive		[optional]	whether to include filesize of files in
	 * 									subdirectories
	 * @return	string	human readable directory size string
	 */
	function get_human_readable_dirsize( $use_selection = null, $pathtodir = null, $recursive = null ) {
		$dirsize = $this->get_dirsize( $use_selection, $pathtodir, $recursive );
		return $this->human_readable_filesize( $dirsize );
	}

	/**
	 * Mapping of file extensions to their expected mime-type
	 *
	 * This mapping does not claim to be exhaustive, but is a good listing for a large amount
	 * of file types.
	 *
	 * Last updated: 2006-08-31
	 *
	 * @link 	http://www.duke.edu/websrv/file-extensions.html
	 * @var		array	$mime_map	key = file extension, value = mime-type
	 */
	var $mime_map = array(
		'ai'	=>	'application/postscript',
		'aif'	=>	'audio/x-aiff',
		'aifc'	=>	'audio/x-aiff',
		'aiff'	=>	'audio/x-aiff',
		'asc'	=>	'text/plain',
		'au'	=>	'audio/basic',
		'avi'	=>	'video/x-msvideo',
		'bcpio'	=>	'application/x-bcpio',
		'bin'	=>	'application/octet-stream',
		'c'		=>	'text/plain',
		'cc'	=>	'text/plain',
		'ccad'	=>	'application/clariscad',
		'cdf'	=>	'application/x-netcdf',
		'class'	=>	'application/octet-stream',
		'cpio'	=>	'application/x-cpio',
		'cpt'	=>	'application/mac-compactpro',
		'csh'	=>	'application/x-csh',
		'css'	=>	'text/css',
		'dcr'	=>	'application/x-director',
		'dir'	=>	'application/x-director',
		'dms'	=>	'application/octet-stream',
		'doc'	=>	'application/msword',
		'drw'	=>	'application/drafting',
		'dvi'	=>	'application/x-dvi',
		'dwg'	=>	'application/acad',
		'dxf'	=>	'application/dxf',
		'dxr'	=>	'application/x-director',
		'eps'	=>	'application/postscript',
		'etx'	=>	'text/x-setext',
		'exe'	=>	'application/octet-stream',
		'ez'	=>	'application/andrew-inset',
		'f'		=>	'text/plain',
		'f90'	=>	'text/plain',
		'fli'	=>	'video/x-fli',
		'gif'	=>	'image/gif',
		'gtar'	=>	'application/x-gtar',
		'gz'	=>	'application/x-gzip',
		'h'		=>	'text/plain',
		'hdf'	=>	'application/x-hdf',
		'hh'	=>	'text/plain',
		'hqx'	=>	'application/mac-binhex40',
		'htm'	=>	'text/html',
		'html'	=>	'text/html',
		'ice'	=>	'x-conference/x-cooltalk',
		'ief'	=>	'image/ief',
		'iges'	=>	'model/iges',
		'igs'	=>	'model/iges',
		'ips'	=>	'application/x-ipscript',
		'ipx'	=>	'application/x-ipix',
		'jpe'	=>	'image/jpeg',
		'jpeg'	=>	'image/jpeg',
		'jpg'	=>	'image/jpeg',
		'js'	=>	'application/x-javascript',
		'kar'	=>	'audio/midi',
		'latex'	=>	'application/x-latex',
		'lha'	=>	'application/octet-stream',
		'lsp'	=>	'application/x-lisp',
		'lzh'	=>	'application/octet-stream',
		'm'		=>	'text/plain',
		'man'	=>	'application/x-troff-man',
		'me'	=>	'application/x-troff-me',
		'mesh'	=>	'model/mesh',
		'mid'	=>	'audio/midi',
		'midi'	=>	'audio/midi',
		'mif'	=>	'application/vnd.mif',
		'mime'	=>	'www/mime',
		'mov'	=>	'video/quicktime',
		'movie'	=>	'video/x-sgi-movie',
		'mp2'	=>	'audio/mpeg',
		'mp3'	=>	'audio/mpeg',
		'mpe'	=>	'video/mpeg',
		'mpeg'	=>	'video/mpeg',
		'mpg'	=>	'video/mpeg',
		'mpga'	=>	'audio/mpeg',
		'ms'	=>	'application/x-troff-ms',
		'msh'	=>	'model/mesh',
		'nc'	=>	'application/x-netcdf',
		'oda'	=>	'application/oda',
		'pbm'	=>	'image/x-portable-bitmap',
		'pdb'	=>	'chemical/x-pdb',
		'pdf'	=>	'application/pdf',
		'pgm'	=>	'image/x-portable-graymap',
		'pgn'	=>	'application/x-chess-pgn',
		'php'	=>	'text/plain',
		'php3'	=>	'text/plain',
		'png'	=>	'image/png',
		'pnm'	=>	'image/x-portable-anymap',
		'pot'	=>	'application/mspowerpoint',
		'ppm'	=>	'image/x-portable-pixmap',
		'pps'	=>	'application/mspowerpoint',
		'ppt'	=>	'application/mspowerpoint',
		'ppz'	=>	'application/mspowerpoint',
		'pre'	=>	'application/x-freelance',
		'prt'	=>	'application/pro_eng',
		'ps'	=>	'application/postscript',
		'qt'	=>	'video/quicktime',
		'ra'	=>	'audio/x-realaudio',
		'ram'	=>	'audio/x-pn-realaudio',
		'ras'	=>	'image/cmu-raster',
		'rgb'	=>	'image/x-rgb',
		'rm'	=>	'audio/x-pn-realaudio',
		'roff'	=>	'application/x-troff',
		'rpm'	=>	'audio/x-pn-realaudio-plugin',
		'rtf'	=>	'text/rtf',
		'rtx'	=>	'text/richtext',
		'scm'	=>	'application/x-lotusscreencam',
		'set'	=>	'application/set',
		'sgm'	=>	'text/sgml',
		'sgml'	=>	'text/sgml',
		'sh'	=>	'application/x-sh',
		'shar'	=>	'application/x-shar',
		'silo'	=>	'model/mesh',
		'sit'	=>	'application/x-stuffit',
		'skd'	=>	'application/x-koan',
		'skm'	=>	'application/x-koan',
		'skp'	=>	'application/x-koan',
		'skt'	=>	'application/x-koan',
		'smi'	=>	'application/smil',
		'smil'	=>	'application/smil',
		'snd'	=>	'audio/basic',
		'sol'	=>	'application/solids',
		'spl'	=>	'application/x-futuresplash',
		'src'	=>	'application/x-wais-source',
		'step'	=>	'application/STEP',
		'stl'	=>	'application/SLA',
		'stp'	=>	'application/STEP',
		'sv4cpio'	=>	'application/x-sv4cpio',
		'sv4crc'	=>	'application/x-sv4crc',
		'swf'	=>	'application/x-shockwave-flash',
		't'		=>	'application/x-troff',
		'tar'	=>	'application/x-tar',
		'tcl'	=>	'application/x-tcl',
		'tex'	=>	'application/x-tex',
		'texi'	=>	'application/x-texinfo',
		'texinfo'	=>	'application/x-texinfo',
		'tif'	=>	'image/tiff',
		'tiff'	=>	'image/tiff',
		'tr'	=>	'application/x-troff',
		'tsi'	=>	'audio/TSP-audio',
		'tsp'	=>	'application/dsptype',
		'tsv'	=>	'text/tab-separated-values',
		'txt'	=>	'text/plain',
		'unv'	=>	'application/i-deas',
		'ustar'	=>	'application/x-ustar',
		'vcd'	=>	'application/x-cdlink',
		'vda'	=>	'application/vda',
		'viv'	=>	'video/vnd.vivo',
		'vivo'	=>	'video/vnd.vivo',
		'vrml'	=>	'model/vrml',
		'wav'	=>	'audio/x-wav',
		'wrl'	=>	'model/vrml',
		'xbm'	=>	'image/x-xbitmap',
		'xlc'	=>	'application/vnd.ms-excel',
		'xll'	=>	'application/vnd.ms-excel',
		'xlm'	=>	'application/vnd.ms-excel',
		'xls'	=>	'application/vnd.ms-excel',
		'xlw'	=>	'application/vnd.ms-excel',
		'xml'	=>	'text/xml',
		'xpm'	=>	'image/x-xpixmap',
		'xwd'	=>	'image/x-xwindowdump',
		'xyz'	=>	'chemical/x-pdb',
		'zip'	=>	'application/zip'
	);

	/**
	 * Valid mime types
	 *
	 * Last updated on 2006-08-31
	 * @link	http://www.iana.org/assignments/media-types/
	 * @var		array		key = Content-type, value = array of valid subtypes for that content-type
	 */
	var $valid_mime_types = array(
		'application'	=>	array( 'activemessage', 'andrew-inset', 'applefile', 'atom+xml',
				'atomicmail', 'batch-SMTP', 'beep+xml', 'cals-1840', 'ccxml+xml', 'cnrp+xml',
				'commonground', 'conference-info+xml', 'cpl+xml', 'csta+xml', 'CSTAdata+xml',
				'cybercash', 'dca-rft', 'dec-dx', 'dialog-info+xml', 'dicom', 'dns', 'dvcs',
				'ecmascript', 'EDI-Consent', 'EDIFACT', 'EDI-X12', 'epp+xml', 'eshop', 'example',
				'fastinfoset', 'fastsoap', 'fits', 'font-tdpfr', 'H224', 'http', 'hyperstudio',
				'iges', 'im-iscomposing+xml', 'index', 'index.cmd', 'index.obj', 'index.response',
				'index.vnd', 'iotp', 'ipp', 'isup', 'javascript', 'json', 'kpml-request+xml',
				'kpml-response+xml', 'mac-binhex40', 'macwriteii', 'marc', 'mathematica', 'mbox',
				'mikey', 'mpeg4-generic', 'mpeg4-iod', 'mpeg4-iod-xmt', 'mp4', 'msword', 'mxf',
				'nasdata', 'news-message-id', 'news-transmission', 'nss', 'ocsp-request',
				'ocsp-response', 'octet-stream', 'oda', 'ogg', 'parityfec', 'pdf', 'pgp-encrypted',
				'pgp-keys', 'pgp-signature', 'pidf+xml', 'pkcs10', 'pkcs7-mime', 'pkcs7-signature',
				'pkix-cert', 'pkixcmp', 'pkix-crl', 'pkix-pkipath', 'pls+xml', 'poc-settings+xml',
				'postscript', 'prs.alvestrand.titrax-sheet', 'prs.cww', 'prs.nprend', 'prs.plucker',
				'rdf+xml', 'qsig', 'reginfo+xml', 'relax-ng-compact-syntax', 'remote-printing',
				'resource-lists+xml', 'riscos', 'rlmi+xml', 'rls-services+xml', 'rtf', 'rtx',
				'samlassertion+xml', 'samlmetadata+xml', 'sbml+xml', 'sdp', 'set-payment',
				'set-payment-initiation', 'set-registration', 'set-registration-initiation',
				'sgml', 'sgml-open-catalog', 'shf+xml', 'sieve', 'simple-filter+xml',
				'simple-message-summary', 'slate', 'smil', //OBSOLETE
				'smil+xml', 'soap+fastinfoset', 'soap+xml', 'spirits-event+xml', 'srgs',
				'srgs+xml', 'ssml+xml', 'timestamp-query', 'timestamp-reply', 'tve-trigger', 'vemmi',
				'vnd.3gpp.bsf+xml', 'vnd.3gpp.pic-bw-large', 'vnd.3gpp.pic-bw-small',
				'vnd.3gpp.pic-bw-var', 'vnd.3gpp.sms', 'vnd.3gpp2.bcmcsinfo+xml', 'vnd.3gpp2.sms',
				'vnd.3M.Post-it-Notes', 'vnd.accpac.simply.aso', 'vnd.accpac.simply.imp',
				'vnd.acucobol', 'vnd.acucorp', 'vnd.adobe.xfdf', 'vnd.aether.imp', 'vnd.amiga.ami',
				'vnd.anser-web-certificate-issue-initiation', 'vnd.apple.installer+xml',
				'vnd.audiograph', 'vnd.autopackage', 'vnd.blueice.multipass', 'vnd.bmi',
				'vnd.businessobjects', 'vnd.canon-cpdl', 'vnd.canon-lips', 'vnd.cinderella',
				'vnd.chipnuts.karaoke-mmd', 'vnd.claymore', 'vnd.commerce-battelle',
				'vnd.commonspace', 'vnd.cosmocaller', 'vnd.contact.cmsg', 'vnd.crick.clicker',
				'vnd.crick.clicker.keyboard', 'vnd.crick.clicker.palette',
				'vnd.crick.clicker.template', 'vnd.crick.clicker.wordbank',
				'vnd.criticaltools.wbs+xml', 'vnd.ctc-posml', 'vnd.cups-pdf', 'vnd.cups-postscript',
				'vnd.cups-ppd', 'vnd.cups-raster', 'vnd.cups-raw', 'vnd.curl', 'vnd.cybank',
				'vnd.data-vision.rdz', 'vnd.dna', 'vnd.dpgraph', 'vnd.dreamfactory',
				'vnd.dvb.esgcontainer', 'vnd.dvb.ipdcesgaccess', 'vnd.dxr', 'vnd.ecdis-update',
				'vnd.ecowin.chart', 'vnd.ecowin.filerequest', 'vnd.ecowin.fileupdate',
				'vnd.ecowin.series', 'vnd.ecowin.seriesrequest', 'vnd.ecowin.seriesupdate',
				'vnd.enliven', 'vnd.epson.esf', 'vnd.epson.msf', 'vnd.epson.quickanime',
				'vnd.epson.salt', 'vnd.epson.ssf', 'vnd.ericsson.quickcall', 'vnd.eudora.data',
				'vnd.ezpix-album', 'vnd.ezpix-package', 'vnd.fdf', 'vnd.ffsns', 'vnd.fints',
				'vnd.FloGraphIt', 'vnd.fluxtime.clip', 'vnd.framemaker', 'vnd.frogans.fnc',
				'vnd.frogans.ltf', 'vnd.fsc.weblaunch', 'vnd.fujitsu.oasys', 'vnd.fujitsu.oasys2',
				'vnd.fujitsu.oasys3', 'vnd.fujitsu.oasysgp', 'vnd.fujitsu.oasysprs',
				'vnd.fujixerox.ART4', 'vnd.fujixerox.ART-EX', 'vnd.fujixerox.ddd',
				'vnd.fujixerox.docuworks', 'vnd.fujixerox.docuworks.binder', 'vnd.fujixerox.HBPL',
				'vnd.fut-misnet', 'vnd.genomatix.tuxedo', 'vnd.grafeq', 'vnd.groove-account',
				'vnd.groove-help', 'vnd.groove-identity-message', 'vnd.groove-injector',
				'vnd.groove-tool-message', 'vnd.groove-tool-template', 'vnd.groove-vcard',
				'vnd.HandHeld-Entertainment+xml', 'vnd.hbci', 'vnd.hcl-bireports',
				'vnd.hhe.lesson-player', 'vnd.hp-HPGL', 'vnd.hp-hpid', 'vnd.hp-hps',
				'vnd.hp-jlyt', 'vnd.hp-PCL', 'vnd.hp-PCLXL', 'vnd.httphone', 'vnd.hzn-3d-crossword',
				'vnd.ibm.afplinedata', 'vnd.ibm.electronic-media', 'vnd.ibm.MiniPay',
				'vnd.ibm.modcap', 'vnd.ibm.rights-management', 'vnd.ibm.secure-container',
				'vnd.igloader', 'vnd.informix-visionary', 'vnd.intercon.formnet',
				'vnd.intertrust.digibox', 'vnd.intertrust.nncp', 'vnd.intu.qbo', 'vnd.intu.qfx',
				'vnd.ipunplugged.rcprofile', 'vnd.irepository.package+xml', 'vnd.is-xpr',
				'vnd.japannet-directory-service', 'vnd.japannet-jpnstore-wakeup',
				'vnd.japannet-payment-wakeup', 'vnd.japannet-registration',
				'vnd.japannet-registration-wakeup', 'vnd.japannet-setstore-wakeup',
				'vnd.japannet-verification', 'vnd.japannet-verification-wakeup',
				'vnd.jisp', 'vnd.kahootz', 'vnd.kde.karbon', 'vnd.kde.kchart', 'vnd.kde.kformula',
				'vnd.kde.kivio', 'vnd.kde.kontour', 'vnd.kde.kpresenter', 'vnd.kde.kspread',
				'vnd.kde.kword', 'vnd.kenameaapp', 'vnd.kidspiration', 'vnd.Kinar',
				'vnd.koan', 'vnd.liberty-request+xml', 'vnd.llamagraphics.life-balance.desktop',
				'vnd.llamagraphics.life-balance.exchange+xml', 'vnd.lotus-1-2-3',
				'vnd.lotus-approach', 'vnd.lotus-freelance', 'vnd.lotus-notes',
				'vnd.lotus-organizer', 'vnd.lotus-screencam', 'vnd.lotus-wordpro',
				'vnd.marlin.drm.mdcf', 'vnd.mcd', 'vnd.medcalcdata', 'vnd.mediastation.cdkey',
				'vnd.meridian-slingshot', 'vnd.mfmp', 'vnd.micrografx.flo', 'vnd.micrografx.igx',
				'vnd.mif', 'vnd.minisoft-hp3000-save', 'vnd.mitsubishi.misty-guard.trustweb',
				'vnd.Mobius.DAF', 'vnd.Mobius.DIS', 'vnd.Mobius.MBK', 'vnd.Mobius.MQY',
				'vnd.Mobius.MSL', 'vnd.Mobius.PLC', 'vnd.Mobius.TXF', 'vnd.mophun.application',
				'vnd.mophun.certificate', 'vnd.motorola.flexsuite', 'vnd.motorola.flexsuite.adsi',
				'vnd.motorola.flexsuite.fis', 'vnd.motorola.flexsuite.gotap',
				'vnd.motorola.flexsuite.kmr', 'vnd.motorola.flexsuite.ttc',
				'vnd.motorola.flexsuite.wem', 'vnd.mozilla.xul+xml', 'vnd.ms-artgalry',
				'vnd.ms-asf', 'vnd.ms-cab-compressed', 'vnd.mseq', 'vnd.ms-excel',
				'vnd.ms-fontobject', 'vnd.ms-htmlhelp', 'vnd.msign', 'vnd.ms-ims', 'vnd.ms-lrm',
				'vnd.ms-powerpoint', 'vnd.ms-project', 'vnd.ms-tnef', 'vnd.ms-wmdrm.lic-chlg-req',
				'vnd.ms-wmdrm.lic-resp', 'vnd.ms-works', 'vnd.ms-wpl', 'vnd.ms-xpsdocument',
				'vnd.musician', 'vnd.music-niff', 'vnd.nervana', 'vnd.netfpx',
				'vnd.noblenet-directory', 'vnd.noblenet-sealer', 'vnd.noblenet-web',
				'vnd.nokia.catalogs', 'vnd.nokia.conml+wbxml', 'vnd.nokia.conml+xml',
				'vnd.nokia.iptv.config+xml', 'vnd.nokia.landmark+wbxml', 'vnd.nokia.landmark+xml',
				'vnd.nokia.landmarkcollection+xml', 'vnd.nokia.pcd+wbxml', 'vnd.nokia.pcd+xml',
				'vnd.nokia.radio-preset', 'vnd.nokia.radio-presets', 'vnd.novadigm.EDM',
				'vnd.novadigm.EDX', 'vnd.novadigm.EXT', 'vnd.oasis.opendocument.chart',
				'vnd.oasis.opendocument.chart-template', 'vnd.oasis.opendocument.formula',
				'vnd.oasis.opendocument.formula-template', 'vnd.oasis.opendocument.graphics',
				'vnd.oasis.opendocument.graphics-template', 'vnd.oasis.opendocument.image',
				'vnd.oasis.opendocument.image-template', 'vnd.oasis.opendocument.presentation',
				'vnd.oasis.opendocument.presentation-template', 'vnd.oasis.opendocument.spreadsheet',
				'vnd.oasis.opendocument.spreadsheet-template', 'vnd.oasis.opendocument.text',
				'vnd.oasis.opendocument.text-master', 'vnd.oasis.opendocument.text-template',
				'vnd.oasis.opendocument.text-web', 'vnd.obn', 'vnd.oma.dd2+xml',
				'vnd.omads-email+xml', 'vnd.omads-file+xml', 'vnd.omads-folder+xml',
				'vnd.omaloc-supl-init', 'vnd.osa.netdeploy', 'vnd.osgi.dp', 'vnd.otps.ct-kip+xml',
				'vnd.palm', 'vnd.paos.xml', 'vnd.pg.format', 'vnd.pg.osasli',
				'vnd.piaccess.application-licence', 'vnd.picsel', 'vnd.pocketlearn',
				'vnd.powerbuilder6', 'vnd.powerbuilder6-s', 'vnd.powerbuilder7',
				'vnd.powerbuilder75', 'vnd.powerbuilder75-s', 'vnd.powerbuilder7-s',
				'vnd.preminet', 'vnd.previewsystems.box', 'vnd.proteus.magazine',
				'vnd.publishare-delta-tree', 'vnd.pvi.ptid1', 'vnd.pwg-multiplexed',
				'vnd.pwg-xhtml-print+xml', 'vnd.qualcomm.brew-app-res', 'vnd.Quark.QuarkXPress',
				'vnd.rapid', 'vnd.RenLearn.rlprint', 'vnd.ruckus.download', 'vnd.s3sms',
				'vnd.scribus', 'vnd.sealed.3df', 'vnd.sealed.csf', 'vnd.sealed.doc',
				'vnd.sealed.eml', 'vnd.sealed.mht', 'vnd.sealed.net', 'vnd.sealed.ppt',
				'vnd.sealed.tiff', 'vnd.sealed.xls', 'vnd.sealedmedia.softseal.html',
				'vnd.sealedmedia.softseal.pdf', 'vnd.seemail', 'vnd.sema',
				'vnd.shana.informed.formdata', 'vnd.shana.informed.formtemplate',
				'vnd.shana.informed.interchange', 'vnd.shana.informed.package',
				'vnd.smaf', 'vnd.solent.sdkm+xml', 'vnd.sss-cod', 'vnd.sss-dtf', 'vnd.sss-ntf',
				'vnd.street-stream', 'vnd.sun.wadl+xml', 'vnd.sus-calendar', 'vnd.svd',
				'vnd.swiftview-ics', 'vnd.syncml.dm+wbxml', 'vnd.syncml.ds.notification',
				'vnd.syncml.+xml', 'vnd.triscape.mxs', 'vnd.trueapp', 'vnd.truedoc',
				'vnd.ufdl', 'vnd.uiq.theme', 'vnd.umajin', 'vnd.uoml+xml', 'vnd.uplanet.alert',
				'vnd.uplanet.alert-wbxml', 'vnd.uplanet.bearer-choice',
				'vnd.uplanet.bearer-choice-wbxml', 'vnd.uplanet.cacheop', 'vnd.uplanet.cacheop-wbxml',
				'vnd.uplanet.channel', 'vnd.uplanet.channel-wbxml', 'vnd.uplanet.list',
				'vnd.uplanet.listcmd', 'vnd.uplanet.listcmd-wbxml', 'vnd.uplanet.list-wbxml',
				'vnd.uplanet.signal', 'vnd.vcx', 'vnd.vectorworks', 'vnd.vd-study',
				'vnd.vidsoft.vidconference', 'vnd.visio', 'vnd.visionary',
				'vnd.vividence.scriptfile', 'vnd.vsf', 'vnd.wap.sic', 'vnd.wap.slc', 'vnd.wap.wbxml',
				'vnd.wap.wmlc', 'vnd.wap.wmlscriptc', 'vnd.webturbo', 'vnd.wfa.wsc',
				'vnd.wordperfect', 'vnd.wqd', 'vnd.wrq-hp3000-labelled', 'vnd.wt.stf',
				'vnd.wv.csp+xml', 'vnd.wv.csp+wbxml', 'vnd.wv.ssp+xml', 'vnd.xara', 'vnd.xfdl',
				'vnd.yamaha.hv-dic', 'vnd.yamaha.hv-script', 'vnd.yamaha.hv-voice',
				'vnd.yamaha.smaf-audio', 'vnd.yamaha.smaf-phrase', 'vnd.yellowriver-custom-menu',
				'vnd.zzazz.deck+xml', 'voicexml+xml', 'watcherinfo+xml', 'whoispp-query',
				'whoispp-response', 'wita', 'wordperfect5.1', 'x400-bp', 'xcap-att+xml',
				'xcap-caps+xml', 'xcap-el+xml', 'xcap-error+xml', 'xenc+xml',
				'xhtml-voice+xml', // OBSOLETE
				'xhtml+xml', 'xml', 'xml-dtd', 'xml-external-parsed-entity', 'xmpp+xml',
				'xop+xml', 'xv+xml', 'zip',
		),
		'audio'	=>	array(
				'32kadpcm', '3gpp', '3gpp2', 'ac3', 'AMR', 'AMR-WB', 'amr-wb+', 'asc',
				'basic', 'BV16', 'BV32', 'clearmode', 'CN', 'DAT12', 'dls', 'dsr-es201108',
				'dsr-es202050', 'dsr-es202211', 'dsr-es202212', 'eac3', 'DVI4', 'EVRC',
				'EVRC0', 'EVRC-QCP', 'example', 'G722', 'G7221', 'G723', 'G726-16',
				'G726-24', 'G726-32', 'G726-40', 'G728', 'G729', 'G729D', 'G729E', 'GSM',
				'GSM-EFR', 'iLBC', 'L8', 'L16', 'L20', 'L24', 'LPC', 'MPA', 'mp4', 'MP4A-LATM',
				'mpa-robust', 'mpeg', 'mpeg4-generic', 'parityfec', 'PCMA', 'PCMU', 'prs.sid',
				'QCELP', 'RED', 'rtp-midi', 'rtx', 'SMV', 'SMV0', 'SMV-QCP', 't140c', 't38',
				'telephone-event', 'tone', 'VDVI', 'VMR-WB', 'vnd.3gpp.iufp', 'vnd.4SB',
				'vnd.audiokoz', 'vnd.CELP', 'vnd.cisco.nse', 'vnd.cmles.radio-events',
				'vnd.cns.anp1', 'vnd.cns.inf1', 'vnd.digital-winds', 'vnd.dlna.adts',
				'vnd.everad.plj', 'vnd.hns.audio', 'vnd.lucent.voice', 'vnd.nokia.mobile-xmf',
				'vnd.nortel.vbk', 'vnd.nuera.ecelp4800', 'vnd.nuera.ecelp7470',
				'vnd.nuera.ecelp9600', 'vnd.octel.sbc',
				'vnd.qcelp', // DEPRECATED - Please use audio/qcelp
				'vnd.rhetorex.32kadpcm', 'vnd.sealedmedia.softseal.mpeg', 'vnd.vmx.cvsd'
		),
		'example'	=>	array(),
		'image '	=>	array(
				'cgm', 'example', 'fits', 'g3fax', 'gif', 'ief', 'jp2', 'jpeg', 'jpm', 'jpx',
				'naplps', 'png', 'prs.btif', 'prs.pti', 't38', 'tiff', 'tiff-fx',
				'vnd.adobe.photoshop', 'vnd.cns.inf2', 'vnd.djvu', 'vnd.dwg', 'vnd.dxf',
				'vnd.fastbidsheet', 'vnd.fpx', 'vnd.fst', 'vnd.fujixerox.edmics-mmr',
				'vnd.fujixerox.edmics-rlc', 'vnd.globalgraphics.pgb', 'vnd.microsoft.icon',
				'vnd.mix', 'vnd.ms-modi', 'vnd.net-fpx', 'vnd.sealed.png',
				'vnd.sealedmedia.softseal.gif', 'vnd.sealedmedia.softseal.jpg', 'vnd.svf',
				'vnd.wap.wbmp', 'vnd.xiff'
		),
		'message'	=>	array(
				'CPIM', 'delivery-status', 'disposition-notification', 'example',
				'external-body', 'http', 'news', 'partial', 'rfc822', 's-http', 'sip',
				'sipfrag', 'tracking-status'
		),
		'model'	=>	array(
				'example', 'iges', 'mesh', 'vnd.dwf', 'vnd.flatland.3dml', 'vnd.gdl',
				'vnd.gs-gdl', 'vnd.gtw', 'vnd.moml+xml', 'vnd.mts', 'vnd.parasolid.transmit.binary',
				'vnd.parasolid.transmit.text', 'vnd.vtu', 'vrml'
		),
		'multipart'	=>	array(
				'alternative', 'appledouble', 'byteranges', 'digest', 'encrypted', 'example',
				'form-data', 'header-set', 'mixed', 'parallel', 'related', 'report', 'signed',
				'voice-message'
		),
		'text'	=>	array(
				'calendar', 'css', 'csv', 'directory', 'dns', 'ecmascript', // OBSOLETE
				'enriched', 'example', 'html', 'javascript', // OBSOLETE
				'parityfec', 'plain', 'prs.fallenstein.rst', 'prs.lines.tag', 'RED',
				'rfc822-headers', 'richtext', 'rtf', 'rtx', 'sgml', 't140',
				'tab-separated-values', 'troff', 'uri-list', 'vnd.abc', 'vnd.curl',
				'vnd.DMClientScript', 'vnd.esmertec.theme-descriptor', 'vnd.fly',
				'vnd.fmi.flexstor', 'vnd.in3d.3dml', 'vnd.in3d.spot', 'vnd.IPTC.NewsML',
				'vnd.IPTC.NITF', 'vnd.latex-z', 'vnd.motorola.reflex', 'vnd.ms-mediapackage',
				'vnd.net2phone.commcenter.command', 'vnd.sun.j2me.app-descriptor', 'vnd.wap.si',
				'vnd.wap.sl', 'vnd.wap.wml', 'vnd.wap.wmlscript', 'xml', 'xml-external-parsed-entity'
		),
		'video'	=>	array(
				'3gpp', '3gpp2', '3gpp-tt', 'BMPEG', 'BT656', 'CelB', 'DV', 'example', 'H261',
				'H263', 'H263-1998', 'H263-2000', 'H264', 'JPEG', 'MJ2', 'MP1S', 'MP2P', 'MP2T',
				'mp4', 'MP4V-ES', 'MPV', 'mpeg', 'mpeg4-generic', 'nv', 'parityfec', 'pointer',
				'quicktime', 'raw', 'rtx', 'SMPTE292M', 'vc1', 'vnd.dlna.mpeg-tts', 'vnd.fvt',
				'vnd.hns.video', 'vnd.motorola.video', 'vnd.motorola.videop', 'vnd.mpegurl',
				'vnd.nokia.interleaved-multimedia', 'vnd.objectvideo', 'vnd.sealed.mpeg1',
				'vnd.sealed.mpeg4', 'vnd.sealed.swf', 'vnd.sealedmedia.softseal.mov',
				'vnd.vivo'
		)
	);

	function getJSObjectMap($path = null) {
		$list = $this->get_filelist( null, $path, true );
		
		$jsList = array();
		foreach ($list as $file) {
			if ($this->check_file_extension($file, 'js')) {
			    $src = file_get_contents($path . '\\' . $file);
				if ($file == "prototype-1.6.0.3.js") {
					$src =  htmlentities($src);
				}
				$oList = array( "name" => $file, "source" => $src);
				$jsList[] = $oList;
			}
		}
	    return $jsList;
	}

}// End of class

//****** END OF FILE ******/
?>