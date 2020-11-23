<?php
class Sonnenbatterie extends IPSModule
   {
      public function Create()
		{
			// Never delete this line!
			parent::Create();
			
			$this->RegisterPropertyString("IP", "");
			$this->RegisterPropertyString("APIKey", "");
         $this->RegisterPropertyInteger("Interval", 30);
         $this->RegisterPropertyBoolean("SaveSums", true);
         $this->RegisterPropertyBoolean("GetStatus", true);
         $this->RegisterAttributeInteger("Today", intval(date("j")));
         
         //Create Variable Profiles ($name, $ProfileType, $Suffix, $MinValue, $MaxValue, $StepSize, $Digits, $Icon)
         $this->CreateVarProfile("SBAT.WATT", 1, " W", 0, 32767, 0, 2, "Electricity");
         $this->CreateVarProfile("SBAT.KILOWATT", 2, " kW", 0, 32000, 0, 2, "Electricity");
         $this->CreateVarProfile("SBAT.LEVEL", 1, " %", 0, 100, 0, 2, "Battery");
         
         // Create Status Profile
         if (!IPS_VariableProfileExists("SBAT.STATUS")) {
            IPS_CreateVariableProfile("SBAT.STATUS", 1);
            IPS_SetVariableProfileValues("SBAT.STATUS", 0, 7, 1);
            IPS_SetVariableProfileAssociation("SBAT.STATUS", 0, "Unbekannt", "Alert", 0xFFFFFF);
            IPS_SetVariableProfileAssociation("SBAT.STATUS", 1, "Normalbetrieb", "EnergySolar", 0xFFFFFF);
            IPS_SetVariableProfileAssociation("SBAT.STATUS", 2, "Stromausfall", "Plug", 0x42a128);
            IPS_SetVariableProfileAssociation("SBAT.STATUS", 3, "Notstrombetrieb", "Battery", 0x42a128);
            IPS_SetVariableProfileAssociation("SBAT.STATUS", 4, "Kein Internet", "Internet", 0xff8800);
            IPS_SetVariableProfileAssociation("SBAT.STATUS", 5, "Überlast", "HollowDoubleArrowUp", 0xff8800);
            IPS_SetVariableProfileAssociation("SBAT.STATUS", 6, "Fehler", "Alert", 0xad0000);
          }
                  
			$this->RegisterTimer("UpdateTimer", $this->ReadPropertyInteger("Interval") * 1000, 'SBAT_UpdateBatteryValues($_IPS[\'TARGET\']);');
		}
      
      public function ApplyChanges() {
         // Never delete this line!
         parent::ApplyChanges();
         
         if (($this->ReadPropertyString("APIKey") != "") && ($this->ReadPropertyString("IP") != "")){
            
            // Set Timer
            $this->SetTimerInterval("UpdateTimer", $this->ReadPropertyInteger("Interval") * 1000);

            $this->MaintainVariable("Consumption_W", "Verbrauch", 1, "SBAT.WATT", 10, true);
            $this->MaintainVariable("GridFeedIn_W", "Netz", 1, "SBAT.WATT", 20, true);
            $this->MaintainVariable("Production_W", "Erzeugung", 1, "SBAT.WATT", 30, true);
            $this->MaintainVariable("Pac_total_W", "Sonnen Batterie", 1, "SBAT.WATT", 40, true);
            $this->MaintainVariable("USOC", "Ladestand", 1, "SBAT.LEVEL", 50, true);
                        
            // Local Power Sums
            $keep = $this->ReadPropertyBoolean("SaveSums");
            $this->MaintainVariable("Consumption_Today", "Verbrauch heute", 2, "SBAT.KILOWATT", 60, $keep);
            $this->MaintainVariable("GridFeedIn_Today", "Netzbezug heute", 2, "SBAT.KILOWATT", 70, $keep);
            $this->MaintainVariable("GridFeedOut_Today", "Einspeisung heute", 2, "SBAT.KILOWATT", 80, $keep);
            $this->MaintainVariable("Production_Today", "Produktion heute", 2, "SBAT.KILOWATT", 90, $keep);
            
            // Local Power History
            $this->MaintainVariable("Consumption_Yesterday", "Verbrauch gestern", 2, "SBAT.KILOWATT", 100, $keep);
            $this->MaintainVariable("GridFeedIn_Yesterday", "Netzbezug gestern", 2, "SBAT.KILOWATT", 110, $keep);
            $this->MaintainVariable("GridFeedOut_Yesterday", "Einspeisung gestern", 2, "SBAT.KILOWATT", 120, $keep);
            $this->MaintainVariable("Production_Yesterday", "Produktion gestern", 2, "SBAT.KILOWATT", 130, $keep);
            
            // Battery Status
            $keep = $this->ReadPropertyBoolean("GetStatus");
            $this->MaintainVariable("BatteryStatus", "Status", 1, "SBAT.STATUS", 140, true);

            $this->SetStatus(102);
         }
         else
         {
            $this->SetStatus(104);
         }         
      }
      
      public function UpdateBatteryValues()
      {
         $ip = $this->ReadPropertyString("IP");
         $api = $this->ReadPropertyString("APIKey");
          
         $opts = array(
             'http'=> array(
                 'method'=>"GET",
                 'max_redirects'=>1,
                 'header'=>"Auth-Token: fcee7300-908a-4307-b326-f16085b36110"
             )
         );

         $context = stream_context_create($opts);

         $url = "http://$ip:80/api/v2/latestdata";

         $this->SendDebug("SBAT Update", "Requesting Information from $url", 0);

         $data = file_get_contents($url, false, $context);

         if ($data === false) {
             throw new Exception("Could not access your Sonnenbatterie!");
         }

         $data = json_decode($data);
         SetValue($this->GetIDForIdent("Consumption_W"), $this->FixupInvalidValue($data->Consumption_W));
         SetValue($this->GetIDForIdent("GridFeedIn_W"), $this->FixupInvalidValue($data->GridFeedIn_W));
         SetValue($this->GetIDForIdent("Production_W"), $this->FixupInvalidValue($data->Production_W));
         SetValue($this->GetIDForIdent("Pac_total_W"), $this->FixupInvalidValue($data->Pac_total_W));
         SetValue($this->GetIDForIdent("USOC"), $this->FixupInvalidValue($data->USOC));
         
         if ($this->ReadPropertyBoolean("SaveSums")) {
            // Save Daily Value and Reset Data On Day Wrap
            if ($this->ReadAttributeInteger("Today") != intval(date("j"))) {
               
               // Set new today value
               $this->WriteAttributeInteger("Today", intval(date("j")));
               
               // Save yesterday's values
               SetValue($this->GetIDForIdent("Consumption_Yesterday"), GetValue($this->GetIDForIdent("Consumption_Today")));
               SetValue($this->GetIDForIdent("GridFeedIn_Yesterday"), GetValue($this->GetIDForIdent("GridFeedIn_Today")));
               SetValue($this->GetIDForIdent("GridFeedOut_Yesterday"), GetValue($this->GetIDForIdent("GridFeedOut_Today")));
               SetValue($this->GetIDForIdent("Production_Yesterday"), GetValue($this->GetIDForIdent("Production_Today")));
               
               // Reset today's values
               SetValue($this->GetIDForIdent("Consumption_Today"), 0.0);
               SetValue($this->GetIDForIdent("GridFeedIn_Today"), 0.0);
               SetValue($this->GetIDForIdent("GridFeedOut_Today"), 0.0);
               SetValue($this->GetIDForIdent("Production_Today"), 0.0);
            }
            
            // Compute daily values
            $devider = 3600 / $this->ReadPropertyInteger("Interval");
            // Consumption
            $value = floatval($this->FixupInvalidValue($data->Consumption_W)) / 1000.0 / $devider;
            $value = $value + GetValue($this->GetIDForIdent("Consumption_Today"));
            $this->SendDebug("SBAT Update", "New Value for Consumption $value", 0);
            SetValue($this->GetIDForIdent("Consumption_Today"), $value); 
            
            // GridFeed
            $value = floatval($this->FixupInvalidValue($data->GridFeedIn_W)) / 1000.0 / $devider;
            if ($value < 0) {
               $value = (-1.0 * $value) + GetValue($this->GetIDForIdent("GridFeedIn_Today"));
               SetValue($this->GetIDForIdent("GridFeedIn_Today"), $value);
            }
            else 
            {
               $value = $value + GetValue($this->GetIDForIdent("GridFeedOut_Today"));
               SetValue($this->GetIDForIdent("GridFeedOut_Today"), $value);
            }
            
            // Production
            $value = floatval($this->FixupInvalidValue($data->Production_W)) / 1000.0 / $devider;
            $value = $value + GetValue($this->GetIDForIdent("Production_Today"));
            SetValue($this->GetIDForIdent("Production_Today"), $value); 
         }
         
         # Status Evaluation
         if ($this->ReadPropertyBoolean("GetStatus")) {
            # read status
            $status = 0;  // "Unbekannt"
            $eclipse_status = $data->ic_status->{'Eclipse Led'};
            $has_protect = $data->ic_status->{'Microgrid Status'}->{'Protect is activated'};
            
            if ($eclipse_status->{'Pulsing White'}) {
               $status = 1;  // "Normalbetrieb"
            }
            if ($eclipse_status->{'Pulsing Green'}) {
               if ($has_protect == 1) {
                  $status = 3;  // "Notstrombetrieb"
               } else {
                  $status = 2;  // "Stromausfall"
               }
            }
            if ($eclipse_status->{'Pulsing Orange'}) {
               if ($has_protect == 1) {
                  $status = 5;  // "Überlast"
               } else {
                  $status = 4;  // "Kein Internet"
               }  
            }
            if ($eclipse_status->{'Solid Red'}) {
               $status = 6;  // "Fehler"
            }
            
            SetValue($this->GetIDForIdent("BatteryStatus"), $status);
         }
      }
      
      // Variablenprofile erstellen
      private function CreateVarProfile($name, $ProfileType, $Suffix, $MinValue, $MaxValue, $StepSize, $Digits, $Icon) {
         if (!IPS_VariableProfileExists($name)) {
            IPS_CreateVariableProfile($name, $ProfileType);
            IPS_SetVariableProfileText($name, "", $Suffix);
            IPS_SetVariableProfileValues($name, $MinValue, $MaxValue, $StepSize);
            IPS_SetVariableProfileDigits($name, $Digits);
            IPS_SetVariableProfileIcon($name, $Icon);
          }
      }
      private function FixupInvalidValue($Value) {
         if(is_numeric($Value)) {
            return intval($Value);
         } else {
            return 0;
         }
      }
   }
?>