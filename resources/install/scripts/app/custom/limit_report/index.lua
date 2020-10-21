--------------------------
--Limit Report
--Ronald First Lua Script

--This Lua script it is to save limit execution information to a external database in order to get a report.
--------------------------

-- lua app_custom.lua limit_report 123456789 123456777
-- app_custom.lua limit_report ${caller_id_number} ${destination_number}

-- load config
	require "resources.functions.config";

--set debug
	-- debug["sql"] = true;

--load libraries
	local log = require "resources.functions.log"["limit_report"]
	--local Database = require "resources.functions.database";
	--local cache = require "resources.functions.cache";
	--local json = require "resources.functions.lunajson";

--get the variables
	domain_name = session:getVariable("domain_name");
	from = session:getVariable("caller_id_number");
	to = session:getVariable("destination_number");
	--domain_uuid = session:getVariable("domain_uuid");
	--context = session:getVariable("context");
	--user = session:getVariable("sip_auth_username")
		--or session:getVariable("username");

--get the argv values
	--from = argv[2];
	--to = argv[3];

--freeswitch.consoleLog("NOTICE", "[LIMIT REPORT] " .. "RONALD LUA TEST limit_report FSLOG" .. "\n")

api = freeswitch.API();

--opts, args, err = require('app.custom.functions.optargs').from_opthelp(opthelp, argv)

--if opts == nil then
	--log.error("Options are not parsable " .. err)

--	message:chat_execute("stop")
   -- do return end
--end

--freeswitch.consoleLog("NOTICE", "[LIMIT REPORT] " .. "argv[2] " .. argv[2] .. "\n")
--freeswitch.consoleLog("NOTICE", "[LIMIT REPORT] " .. "argv[3] " .. argv[3] .. "\n")
--freeswitch.consoleLog("NOTICE", "[LIMIT REPORT] " .. "domain_name " .. domain_name .. "\n")

log.info("RONALD LUA; limit concurrence calls was reached for ".. domain_name .." and the information will be send to limit_report external database!")
--log.info("argv[2] " .. argv[2])
--log.info("argv[3] " .. argv[3])
--log.info("Domain_name " .. domain_name)


-- Send to the external database using curl
-- sms_message_text = sms_message_text:gsub(" ","___")
limit = "curl https://domain.com/handler.php?from=" .. from .. "&to=" .. to .. "&domain=" .. domain_name
--MMS = true

--if opts.d then log.info("Using CURL command " .. mmscmd) end

log.info("Using CURL command " .. limit)
api:executeString(limit)

