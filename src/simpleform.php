<?php
/**
 * simple form generator class
 *
 * @package        SimpleClasses/SimpleForm
 * @author         Björn Bartels <coding@bjoernbartels.earth>
 * @link           https://gitlab.bjoernbartels.earth/groups/php
 * @license        http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 * @copyright      copyright (c) 2007 Björn Bartels <coding@bjoernbartels.earth>
 */
namespace SimpleForm;

class SimpleForm {
	
	public $_tpl = NULL;
	
	public $_db = NULL;
	
	public $_conNotification = NULL;
	
	public $_notifications = array();
	
	public $data = array();
	
	public $options = array();
	
	public $tags = array(
		"container" =>	"form",
		"row"		=>	"div",
		"cell"		=>	"span",
		"labelcell"	=>	"label"
	);
	
	public $types = array("text", "password", "button", "file", "radio", "checkbox", "textarea", "select", "hidden");
	
	/**
	 * class constructor
	 * @param OBJECT|ARRAY $options
	 * @return ProductTable
	 */
	public function __construct ( $options ) {
		$this->tags = (object)$this->tags;
		$this->setOptions($options);
		return $this;
	}
	
	/**
	 * generate form header mark-up
	 * @param	OBJECT	$aHTMLFormOptions
	 * @return	SimpleForm
	 */
	public function buildFormHeader ( $aHTMLFormOptions = NULL ) {
		$sHTMLFormHeader = "";
		$aFormOptions = $this->getOptions("footer");
		if (!$aFormOptions) {
			$aFormOptions = array();
		}
		if ($this->getOptions("buttons_top") === true) {
			$sHTMLFormHeader = "";
		}
		$this->getTpl()->set('s', 'FORMHEADER', $sHTMLFormHeader);
		return ($this);
	}
		
	/**
	 * generate form footer mark-up
	 * @param	OBJECT	$aHTMLFormOptions
	 * @return	SimpleForm
	 */
	public function buildFormFooter ( $aHTMLFormOptions = NULL ) {
		$sHTMLFormFooter = "";
		$aFormOptions = $this->getOptions("footer");
		if (!$aFormOptions) {
			$aFormOptions = array();
		}
		if ($this->getOptions("buttons_bottom") !== false) {
			$sHTMLFormFooter = "";
		}
		$this->getTpl()->set('s', 'FORMFOOTER', $sHTMLFormFooter);
		return ($this);
	}
	
	/**
	 * generate hidden form body mark-up
	 * @param	ARRAY	$aFieldData
	 * @param	OBJECT	$aHTMLFormOptions
	 * @return	SimpleForm
	 */
	public function buildFormHidden ($aFieldData, $aHTMLFormOptions = NULL) {
		$sHTMLFormHidden = "";
		$this->getTpl()->set('s', 'FORMHIDDEN', $sHTMLFormHidden);
		return ($this);
	}
	
	/**
	 * generate form body mark-up
	 * @param	ARRAY	$aFieldData
	 * @param	OBJECT	$aHTMLFormOptions
	 * @return	SimpleForm
	 */
	public function buildFormBody ($aFieldData, $aHTMLFormOptions = NULL) {
		$aRows = array();
		$this->_aRows = array();
		$sFormID = $this->getOptions("formID");
		
		foreach ( (array)$aFieldData as $iRow => $oFieldData ) {
			$sHTML			=	"";
			$bRequired		=	($oFieldData["required"] === true);
			$bError			=	($oFieldData["error"] != false);
			$sClassnames	=	
				"formInput".
				" ".$oFieldData["field"]."".
				(($bRequired) ? " required" : "").
				(($bError) ? " error" : "");
				
			switch ($oFieldData["type"]) {
				case "select":
					$sHTML = "<select ".
						"id=\"".$oFieldData["fieldID"]."\" ".
						"name=\"".$oFieldData["field"]."\" ".
						"class=\"".$sClassnames."\" ".
						"size=\"".(((int)$oFieldData["size"] > 0) ? (int)$oFieldData["size"] : 1)."\" ".
					">".
						"!_SELECTOPTIONS_".$oFieldData["field"]."_!".
					"</select>";
					$sSelectOptions = "";
					if ( is_array($oFieldData["options"]) ) {
						foreach ($oFieldData["options"] as $key => $oOption) {
							$sSelectOptions .= "<option ".
								"value=\"".$oOption["value"]."\" ".
									( ( !is_array($oFieldData["value"]) && ($oOption["value"] == $oFieldData["value"]) ) ? "selected=\"selected\" " : "" ).
									( ( is_array($oFieldData["value"]) && in_array($oOption["value"], $oFieldData["value"]) ) ? "selected=\"selected\" " : "" ).
							">".
								"".$oOption["text"]."".
							"</option>";
						}
					}
					$sHTML = str_replace("!_SELECTOPTIONS_".$oFieldData["field"]."_!", $sSelectOptions, $sHTML);
				break;
				case "radio":
				case "checkbox":
					$sHTML = "<ul>";
					if ( is_array($oFieldData["options"]) ) {
						foreach ($oFieldData["options"] as $key => $oOption) {
							$sHTML .= "<li>".
								"<label>".
									"<input ".
										"id=\"".$oFieldData["fieldID"]."_".$key."\" ".
										"type=\"".$oFieldData["type"]."\" ".
										"name=\"".$oFieldData["field"]."\" ".
										"class=\"".$sClassnames."\" ".
										"value=\"".$oOption["value"]."\" ".
											( ( !is_array($oFieldData["value"]) && ($oOption["value"] == $oFieldData["value"]) ) ? "checked=\"checked\" " : "" ).
											( ( is_array($oFieldData["value"]) && in_array($oOption["value"], $oFieldData["value"]) ) ? "checked=\"checked\" " : "" ).
									"/>".
									"".$oOption["text"]."".
								"</label>".
							"</li>";
						}
					} else {
						$sHTML = "";
					}
					if ( !empty($sHTML) ) {
						$sHTML .= "</ul>";
					}
				break;
				case "textarea":
					$sHTML = "<textarea ".
						"id=\"".$oFieldData["fieldID"]."\" ".
						"name=\"".$oFieldData["field"]."\" ".
						"class=\"".$sClassnames."\" ".
						(((int)$oFieldData["rows"] > 0) ? "rows=\"".((int)$oFieldData["rows"])."\" " : "").
						(((int)$oFieldData["cols"] > 0) ? "cols=\"".((int)$oFieldData["cols"])."\" " : "").
					">".
						$oFieldData["value"].
					"</textarea>";
				break;
				
				default:
					if ( !in_array( $oFieldData["type"], $this->types ) ) {
						$sType = "text";
					} else  {
						$sType = strtolower($oFieldData["type"]);
					}
					$sHTML = "<input ".
						"id=\"".$oFieldData["fieldID"]."\" ".
						"type=\"".$sType."\" ".
						"name=\"".$oFieldData["field"]."\" ".
						"class=\"".$sClassnames."\" ".
						"value=\"".$oFieldData["value"]."\" ".
						(((int)$oFieldData["size"] > 0) ? "size=\"".((int)$oFieldData["size"])."\" " : "").
					"/>";
				break;
			}
			if ( isset($oFieldData["setHTML"]) && !empty($oFieldData["setHTML"]) ) {
				$sHTML = trim($oFieldData["setHTML"], " \n\t\r");
			}
			$aRows[] = $sHTML;
		}
		$this->_aRows = $aRows;
		
		foreach ($aRows as $iRow => $sRow) {
			$this->getTpl()->set('d', 'FORMINPUTID', $aFieldData[$iRow]["fieldID"]);
			$this->getTpl()->set('d', 'REQUIRED', ((($aFieldData[$iRow]["required"] === true)) ? " required" : "") );
			$this->getTpl()->set('d', 'ERROR', (($aFieldData[$iRow]["error"] != false) ? " error" : "") );
			$this->getTpl()->set('d', 'LABEL', $aFieldData[$iRow]["title"].( ( ($aFieldData[$iRow]["required"] === true) && ($aFieldData[$iRow]["type"] != "hidden") ) ? " *" : "" ) );
			$this->getTpl()->set('d', 'INPUTHTML', $sRow);
			if (($iRow % 2) == 0) {
				$this->getTpl()->set('d', 'CSS_CLASS', 'even');
			} else {
				$this->getTpl()->set('d', 'CSS_CLASS', 'odd');
			}
			$this->getTpl()->next();
		
		}
		return ($this);
	}
	
	/**
	 * generate mini table mark-up template
	 * @return STRING
	 */
	public function buildMarkupTemplate () {
		$aHTML = array(
			"<".$this->tags->container." id=\"{FORMID}\" action=\"{FORMACTION}\" method=\"{FORMMETHOD}\" >",
				"<".$this->tags->row." class=\"formHeader\" >",
					"{FORMHEADER}",
				"</".$this->tags->row.">",
					"<!-- BEGIN:BLOCK -->",
						"<".$this->tags->row." class=\"formRow{REQUIRED}{ERROR}\" >",
							"<".$this->tags->labelcell." for=\"{FORMINPUTID}\">",
								"<".$this->tags->cell." class=\"labelSpan\">",
									"{LABEL}",
								"</".$this->tags->cell.">",
								"<".$this->tags->cell." class=\"inputSpan\">",
									"{INPUTHTML}",
								"</".$this->tags->cell.">",
							"</".$this->tags->labelcell.">",
						"</".$this->tags->row.">",
					"<!-- END:BLOCK -->",
				"<".$this->tags->row." class=\"formFooter\">",
					"{FORMFOOTER}",
				"</".$this->tags->row.">",
				"{FORMHIDDEN}",
			"</".$this->tags->container.">"
		);
		$sHTML = implode("", $aHTML);
		return $sHTML;
	}
	
	/**
	 * generate table mark-up
	 * @return STRING
	 */
	public function buildMarkup () {
		$sHTML = "";
	
		$sFormID = $this->getOptions("formID");
		if (!$sFormID) {
			$sFormID = "form" . md5(microtime());
			$this->options["formID"] = $sFormID;
		}
		$sFormMethod = $this->getOptions("method");
		if ( !in_array( strtolower($sFormMethod), array("post","get") ) ) {
			$sFormMethod = "get";
			$this->options["method"] = strtolower($sFormMethod);
		}
		foreach ( (array)$this->options["fields"] as $iRow => $oFieldData ) {
			$this->options["fields"][$iRow]["fieldID"] = $sFormID ."_". $oFieldData["field"];
		}
		
		$this->getTpl()->reset();
		$this->getTpl()->set('s', 'FORMID',			$sFormID );
		$this->getTpl()->set('s', 'FORMMETHOD',		$sFormMethod );
		$this->getTpl()->set('s', 'FORMACTION',		$this->getFormAction() );
		$this->getTpl()->set('s', 'SUBMIT_TEXT',	$this->getOptions("submittext") );
		$this->getTpl()->set('s', 'NOTIFICATION',	$this->getNotifications() );
		$this->buildFormHeader( $this->getOptions("header") );
		$this->buildFormFooter( $this->getOptions("footer") );
		$this->buildFormBody( $this->getOptions("fields") );
		$this->buildFormHidden( $this->getOptions("fields") );
		$sTemplate = $this->getOptions("template");
		if ($sTemplate == "") {
			$sTemplate = $this->buildMarkupTemplate();
		}
		$sHTML = $this->getTpl()->generate( $sTemplate, true );
		
		return $sHTML;
	}
	
	/**
	 * generate table JSON data
	 * @return STRING
	 */
	public function buildData () {
		$sJSON = "[]";
		if ($this->data) {
			$sJSON = json_encode($this->data);
		}
		return $sJSON;
	}
	
	/**
	 * return template object
	 * @return Template
	 */
	public function getTpl() {
		if ($this->_tpl == NULL) {
			$this->setTpl();
		}
		return $this->_tpl;
	}

	/**
	 * generate template object
	 * @param Template $_tpl
	 * @return ProductTable
	 */
	public function setTpl( $_tpl = NULL ) {
		if ($_tpl == NULL) {
			$this->_tpl = new Template;
		} else {
			$this->_tpl = $_tpl;
		}
		return ($this);
	}
	/**
	 * generate database object
	 * @return DB_Contenido
	 */
	public function getDb() {
		if ($this->_db == NULL) {
			$this->setDb();
		}
		return $this->_db;
	}

	/**
	 * generate database object
	 * @param DB_Contenido $_db
	 * @return ProductTable
	 */
	public function setDb( $_db = NULL ) {
		if ($_db == NULL) {
			$this->_db = new DB_Contenido();
		} else {
			$this->_db = $_db;
		}
		return ($this);
	}
	
	/**
	 * return form data or full fields information
	 * @param	BOOLEAN	$bFull
	 * @return	MIXED
	 */
	public function getData( $bFull = false ) {
		if ( $bFull ) {
			return ($this->getOptions("fields"));
		}
		return $this->data;
	}

	/**
	 * set new table data
	 * @param MIXED $data
	 * @return SimpleForm
	 */
	public function setData( $data = NULL) {
		if ( is_array($data) ) {
			$aFields = $this->getOptions("fields");
			$this->data = array();
			foreach ((array)$aFields as $key => $oField) {
				if ($oField) {
					$this->data[$oField["field"]] = $data[$oField["field"]];
					$this->_setFieldValue($oField["field"], $data[$oField["field"]]);
				}
			}
		}
		return $this;
	}

	/**
	 * set field value
	 * @param	STRING	$sName
	 * @param	MIXED	$mValue
	 * @return	SimpleForm
	 */
	private function _setFieldValue ( $sName, $mValue = NULL ) {
		$aFields = $this->getOptions("fields");
		foreach ((array)$aFields as $key => $oField) {
			if ($sName == $oField["field"]) {
				$this->options["fields"][$key]["value"] = $mValue;
			}
		}
		return ($this);
	}
	
	/**
	 * get field data by field name
	 * @param	STRING	$sName
	 * @param	ARRAY	$mValue
	 * @return	ARRAY|BOOLEAN
	 */
	private function _getFieldByName ( $sName = "" ) {
		$aFields = $this->getOptions("fields");
		foreach ((array)$aFields as $key => $oField) {
			if ($sName == $oField["field"]) {
				return $oField;
			}
		}
		return (false);
	}
	
	/**
	 * return option by key or complete option set
	 * @param	STRING $key	
	 * @return	MIXED
	 */
	public function getOptions( $key = "" ) {
		if ( !empty($key) ) { 
			if ( isset($this->options[$key]) ) {
				return $this->options[$key];
			} else {
				return false;
			}
		}
		return $this->options;
	}

	/**
	 * set options by given option set
	 * @param OBJECT|ARRAY $options
	 * @return ProductTable
	 */
	public function setOptions($options) {
		if ( is_array($options) ) {
			$this->options = array_merge($this->options, $options);
		} else if ( is_object($options) ) {
			$this->options = array_merge($this->options, (array)$options);
		} else {
			throw new Exception("invalid table options");
		}
		foreach ($this->options as $key => $value) {
			$sLower = strtolower($key);
			if ($key != $sLower) {
				$this->options[$sLower] = $value;
				unset($this->options[$key]);
			}
		}
		if ( isset($options["data"]) ) {
			$this->setData($options["data"]);
			unset( $this->options["data"] );
		}
		if ( isset($options["notification"]) ) {
			$this->addNotification($options["notification"]);
			unset( $this->options["notification"] );
		}
		return $this;
	}


	/**
	 * map form (my)SQL columns data as recieved with DESCRIBE or SHOW TABLE COLUMNS
	 * @param	ARRAY	$aColumns
	 * @return	ARRAY
	 */
	public function mapColumns( $aColumns ) {
		$aItems = array();
		foreach ((array)$aColumns as $key => $aColumn) {
			$item = array(
				"field"	=>	$aColumn["Field"]
			);
			$aItems[] = $item;
		}
		return ($aItems);
	}

	/**
	 * set form input elements to given type
	 * @param	ARRAY	$aFields
	 * @param	STRING	$sType
	 * @return ProductTable
	 */
	public function setType( $aFields, $sType = NULL ) {
		if ( !in_array( $sType, $this->types ) ) {
			$sType = "text";
		} else  {
			$sType = strtolower($sType);
		}
		foreach ((array)$this->options["fields"] as $key => $value) {
			if ( in_array((array)$this->options["fields"][$key]["field"], (array)$aFields) ) {
				$this->options["fields"][$key]["type"] = $sType;
			}
		}
		return $this;
	}

	/**
	 * set form input elements to 'hidden' type
	 * @param	ARRAY	$aFields
	 * @return	ProductTable
	 */
	public function setHidden( $aFields ) {
		return $this->setType( $aFields, "hidden" );
	}

	/**
	 * get form action URL
	 * @return	STRING
	 */
	public function getFormAction( ) {
		return $this->getOptions("action");
	}

	/**
	 * set form action URL
	 * @param	STIRNG	$sAction
	 * @return	ProductTable
	 */
	public function setFormAction( $sAction ) {
		$this->options["action"] = $sAction;
		return ($this);
	}
	
	/**
	 * @return the $_notifications
	 */
	public function getNotifications ( $sMode = "" ) {
		switch ($sMode) {
			case 1 :
				return (array)$this->_notifications;
			break;
			
			default:
				return implode("", $this->_notifications);
			break;
		}
	}

	/**
	 * @param field_type $_notifications
	 */
	public function addNotification ( $mNotifications ) {
		if (is_array($mNotifications)) {
			$this->_notifications = array_merge($this->_notifications, $mNotifications);
		} else if (!empty($mNotifications)) {
			$this->_notifications = array_merge($this->_notifications, array($mNotifications));
		}
	}

	/**
	 * @param field_type $_notifications
	 */
	public function resetNotifications () {
		$this->_notifications = array();
	}

	/**
	 * perform form value validation
	 * @param	BOOLEAN	$bNotifications
	 * @param	BOOLEAN	$bTrimValues
	 * @param	ARRAY	$aFieldData
	 * @return	BOOLEAN
	 */
	public function validateForm ( $bNotifications = true, $bTrimValues = true, $aFieldData = null ) {
		if (!is_array($aFieldData)) {
			$aFieldData = $this->getData(true);
		}
		$bError = false;
		$aErrors = array(
			"required" => array(),
			"type" => array(),
			"name" => array()
		);
		foreach ( (array)$aFieldData as $iRow => $oFieldData ) {
			if ($bTrimValues) {
				$oFieldData["value"] = trim($oFieldData["value"]);
			}
			$sMethodPrefix	= "validate_";
			$sValidateName	= $sMethodPrefix."".$oFieldData["field"];
			$sValidateType	= $sMethodPrefix."".$oFieldData["type"];
			$mFieldValidate	= true;
			$sErrorText		= (isset($oFieldData["errormsg"]) && !empty($oFieldData["errormsg"])) ? $oFieldData["errormsg"] : $oFieldData["title"];
			if ( method_exists($this, $sValidateName) ) {
				// check field name type validation (if exists "$this->validate_{name}")
				$mFieldValidate = call_user_method($sValidateName, $this, $oFieldData);
				if ( $mFieldValidate !== true ) {
					$aErrors["name"][] = (!is_string($mFieldValidate)) ? $sErrorText : $mFieldValidate;
				}
			}
			if ( method_exists($this, $sValidateType) ) {
				// check input type validation (if exists "$this->validate_{type}")
				$mFieldValidate = call_user_method($sValidateType, $this, $oFieldData);
				if ( $mFieldValidate !== true ) {
					$aErrors["type"][] = (!is_string($mFieldValidate)) ? $sErrorText : $mFieldValidate;
				}
			}
			if ( isset($oFieldData["required"]) && ($oFieldData["required"] === true) ) {
				// check field value required validation
				$mFieldValidate = $this->_validate_required($oFieldData);
				if ( $mFieldValidate !== true ) {
					$aErrors["required"][] = (!is_string($mFieldValidate)) ? $sErrorText : $mFieldValidate;
				}
			}
			if ($mFieldValidate !== true) {
				$bError = true;
				$oFieldData["error"] = true;
			}
			$this->options["fields"][$iRow] = $oFieldData;
		}
		if ( $bError && $bNotifications ) {
			$aErrorText = array();
			if (count($aErrors["required"]) > 0) {
				$sErrorFields = implode(", ", $aErrors["required"]);
				$aErrorText[] = sprintf(i18n("Folgende Felder m&uuml;ssen ausgef&uuml;llt werden: %s!") , $sErrorFields);
			}
			if (count($aErrors["type"]) > 0) {
				$sErrorFields = implode(", ", $aErrors["type"]);
				$aErrorText[] = sprintf(i18n("Folgende Felder haben einen ung&uuml;ltigen Wert: %s!") , $sErrorFields);
			}
			if (count($aErrors["name"]) > 0) {
				$sErrorFields = implode(", ", $aErrors["name"]);
				$aErrorText[] = sprintf(i18n("Folgende Felder sind nicht korrekt: %s!") , $sErrorFields);
			}
			$this->addNotification(array(
				"error" => $this->getConNotification()->messageBox(
					($this->getOptions("errorlevel") == "error" ? "error" : "warning"), 
					implode("<br />", $aErrorText), 
					$style
				)
			));
		}
		if ($bError) {
			return (false);
		}
		return (true);
	}

	/**
	 * check for required value
	 * @param	BOOLEAN	$bNotifications
	 */
	private function _validate_required ( $oFieldData = null ) {
		if ($oFieldData && ($oFieldData["required"] != true)) {
			return (true);
		}
		if (!$oFieldData || !isset($oFieldData["value"]) || empty($oFieldData["value"])) {
			return (false);
		}
		return (true);
	}
	
	/**
	 * get Contenido notification object
	 * @return Contenido_Notification
	 */
	public function getConNotification() {
		if ($this->_conNotification == NULL) {
			$this->setConNotification();
		}
		return $this->_conNotification;
	}

	/**
	 * set Contenido notification object
	 * @param Contenido_Notification $_conNotification
	 */
	public function setConNotification($_conNotification = NULL) {
		if ($_conNotification == NULL) {
			$this->_conNotification = new Contenido_Notification();
		} else {
			$this->_conNotification = $_conNotification;
		}
		return ($this);
	}

	
}