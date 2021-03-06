<?php

    function msgQueryProfile( $aRequest )
    {
        if ( validUser() )
        {
            global $gGame;
            loadGameSettings();
            
            $Out = Out::getInstance();
            $UserId = UserProxy::getInstance()->UserId;
    
            if ( validAdmin() && isset($aRequest["userId"]) && ($aRequest["userId"]!=0) )
            {
                $UserId = intval( $aRequest["userId"] );
            }
    
            $Connector = Connector::getInstance();
    
            $Out->pushValue("show", $aRequest["showPanel"]);
    
            // Admintool relevant data
    
            $Users = $Connector->prepare( "SELECT Login, UNIX_TIMESTAMP(Created) AS CreatedUTC, ExternalBinding, BindingActive FROM `".RP_TABLE_PREFIX."User` WHERE UserId = :UserId LIMIT 1" );
            $Users->bindValue( ":UserId", intval($UserId), PDO::PARAM_INT );
    
            $Data = $Users->fetchFirst();
    
            if ($Data != null)
            {
                $Out->pushValue("userid", $UserId);
                $Out->pushValue("name", $Data["Login"]);
                $Out->pushValue("bindingActive", $Data["BindingActive"] == "true");
                $Out->pushValue("binding", $Data["ExternalBinding"]);
    
                $CreatedUTC = $Data["CreatedUTC"];
            }
    
            // Load settings
    
            $SettingsQuery = $Connector->prepare( "SELECT * FROM `".RP_TABLE_PREFIX."UserSetting` WHERE UserId = :UserId" );
            $SettingsQuery->bindValue(":UserId", intval($UserId), PDO::PARAM_INT);
            $UserSettings = array();
    
            $SettingsQuery->loop(function($Data) use (&$UserSettings)
            {
                $UserSettings[$Data["Name"]] = array("number" => $Data["IntValue"], "text" => $Data["TextValue"]);
            });
    
            $Out->pushValue("settings", $UserSettings);
    
            // Load characters
    
            $Characters = Array();
    
            if ( $UserId == UserProxy::getInstance()->UserId )
            {
                foreach ( UserProxy::getInstance()->Characters as $Data )
                {
                    if ( $Data->Game == $gGame["GameId"])
                    {
                        $Character = Array(
                            "id"        => $Data->CharacterId,
                            "name"      => $Data->Name,
                            "classname" => explode(":", $Data->ClassName),
                            "mainchar"  => $Data->IsMainChar,
                            "role1"     => $Data->Role1,
                            "role2"     => $Data->Role2
                        );
        
                        array_push($Characters, $Character);
                    }
                }
            }
            else
            {
                $CharacterQuery = $Connector->prepare( "SELECT * FROM `".RP_TABLE_PREFIX."Character` ".
                    "WHERE UserId = :UserId AND Game = :Game ".
                    "ORDER BY Mainchar, Name" );
    
                $CharacterQuery->bindValue(":UserId", intval($UserId), PDO::PARAM_INT);
                $CharacterQuery->bindValue(":Game", $gGame["GameId"], PDO::PARAM_STR);
    
                $CharacterQuery->loop( function($Row) use (&$Characters)
                {
                    $Character = Array(
                        "id"        => $Row["CharacterId"],
                        "name"      => $Row["Name"],
                        "classname" => explode(":", $Row["Class"]),
                        "mainchar"  => $Row["Mainchar"] == "true",
                        "role1"     => $Row["Role1"],
                        "role2"     => $Row["Role2"]
                    );
    
                    array_push($Characters, $Character);
                });
            }
    
            $Out->pushValue("character", $Characters);
    
            // Total raid count
    
            $NumRaids = 0;
            $RaidsQuery = $Connector->prepare( "SELECT COUNT(*) AS `NumberOfRaids` FROM `".RP_TABLE_PREFIX."Raid` ".
                "LEFT JOIN `".RP_TABLE_PREFIX."Location` USING(LocationId) ".
                "WHERE Start > FROM_UNIXTIME(:Created) AND Start < FROM_UNIXTIME(:Now) AND Game = :Game" );
            
            $RaidsQuery->bindValue( ":Now", time(), PDO::PARAM_INT );
            $RaidsQuery->bindValue( ":Created", $CreatedUTC, PDO::PARAM_STR );
            $RaidsQuery->bindValue( ":Game", $gGame["GameId"], PDO::PARAM_STR );
            
            $Data = $RaidsQuery->fetchFirst();
            if ($Data != null)
                $NumRaids = $Data["NumberOfRaids"];
    
            // Load attendance
    
            $AttendanceQuery = $Connector->prepare(  "Select `Status`, `Role`, COUNT(*) AS `Count` ".
                "FROM `".RP_TABLE_PREFIX."Attendance` ".
                "LEFT JOIN `".RP_TABLE_PREFIX."Raid` USING(RaidId) ".
                "LEFT JOIN `".RP_TABLE_PREFIX."Location` USING(LocationId) ".
                "WHERE UserId = :UserId AND Start > FROM_UNIXTIME(:Created) AND Start < FROM_UNIXTIME(:Now) AND Game = :Game ".
                "GROUP BY `Status`, `Role` ORDER BY Status" );
    
            $AttendanceQuery->bindValue( ":UserId", intval($UserId), PDO::PARAM_INT );
            $AttendanceQuery->bindValue( ":Created", intval($CreatedUTC), PDO::PARAM_INT );
            $AttendanceQuery->bindValue( ":Now", time(), PDO::PARAM_INT );
            $AttendanceQuery->bindValue( ":Game", $gGame["GameId"], PDO::PARAM_STR );
    
            $AttendanceData = array(
                "raids"       => $NumRaids,
                "available"   => 0,
                "unavailable" => 0,
                "ok"          => 0,
                "roles"       => Array() );
    
            // Pull data
    
            $AttendanceQuery->loop( function($Data) use (&$AttendanceData)
            {
                if ($Data["Status"] != "undecided")
                {
                    $AttendanceData[$Data["Status"]] += $Data["Count"];
                }
                
                if ($Data["Status"] == "ok")
                {
                    $RoleId = $Data["Role"];                
                    if (isset($AttendanceData["roles"][$RoleId]))
                        $AttendanceData["roles"][$RoleId] += $Data["Count"];
                    else
                        $AttendanceData["roles"][$RoleId] = $Data["Count"];
                    
                }
            });
    
            $Out->pushValue("attendance", $AttendanceData);
        }
        else
        {
            $Out = Out::getInstance();
            $Out->pushError(L("AccessDenied"));
        }
    }

?>