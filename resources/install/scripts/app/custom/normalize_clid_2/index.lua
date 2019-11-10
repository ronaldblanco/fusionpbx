-- Set effective_caller_id name and number regarding different rules, lise outbound and also..

opthelp = [[
 -n, --no-ani                       Do not prepend ANI
 -m, --mode=OPTARG                  Could be simple or advanced. On advanced mode it says "hello-file" after "loops" count and run again for "post-loops"
 -s, --temporary-storage=OPTARG     Temporary storage
]]

opts, args, err = require('app.custom.functions.optargs').from_opthelp(opthelp, argv)

if opts == nil then
    freeswitch.consoleLog("ERROR", "[silence_detect] Options are not parsable " .. err)
    do return end
end


if ( session:ready() ) then

    arguments = "";
    for key,value in pairs(argv) do
        if (key > 1) then
            arguments = arguments .. " '" .. value .. "'"
            freeswitch.consoleLog("notice", "[NORMALIZE CALLERID] Modifiers: argv["..key.."]: "..argv[key].."\n")
        end
    end

    -- prepend_ani - Perpend ANI to callerID
    prepend_ani = argv[2] or "true"

    -- callerid_prefix - What to prepend to callerID.
    callerid_prefix = argv[3] or ''
        
    ani = session:getVariable("ani")
    ani = ani:gsub("%D", "")

    -- Check if call is forwarded
    if (ani:len() > 5) then -- Seems, we have forwaded call
        freeswitch.consoleLog("notice", "[NORMALIZE CALLERID] Call is forwarded. Setting callerid to " .. callerid_prefix .. ani .. "\n")
        session:setVariable("effective_caller_id_name", callerid_prefix .. ani)
        session:setVariable("effective_caller_id_number", callerid_prefix .. ani)
        do return end
    end

    outbound_caller_id_name = session:getVariable("outbound_caller_id_name") or ""

    if string.find(outbound_caller_id_name:lower(), "anon") then
        freeswitch.consoleLog("notice", "[NORMALIZE CALLERID] Call is anonymous.\n")
        -- No matter what to do, just set callerid to Anon
        session:setVariable("effective_caller_id_name", "anonymous")
        session:setVariable("effective_caller_id_number", "anonymous")
        session:setVariable("sip_cid_type","rpid")
        session:setVariable("origination_privacy", "screen+hide_name+hide_number")
        do return end
    end

    -- Check for outbound callerid number...
    outbound_caller_id_number = session:getVariable("outbound_caller_id_number") or ""

    if (outbound_caller_id_number:len() > 0) then

        outbound_caller_id_number = outbound_caller_id_number:gsub("%D", "")

        outbound_caller_id_name = outbound_caller_id_name:len() > 0 and outbound_caller_id_name or outbound_caller_id_number

        freeswitch.consoleLog("notice", "[NORMALIZE CALLERID] Callerid is set based on user settings " .. outbound_caller_id_name .. "(" .. outbound_caller_id_number .. ")\n")

        session:setVariable("effective_caller_id_name", callerid_prefix .. outbound_caller_id_name)
        session:setVariable("effective_caller_id_number", callerid_prefix .. outbound_caller_id_number)
        do return end
    end

    -- Normal outbound call
    company_caller_id = session:getVariable("company_caller_id")

    if (company_caller_id) then
        company_caller_id = company_caller_id:gsub("%D", "")
        if (prepend_ani == 'true') then
            company_caller_id = company_caller_id .. ani
        end
        freeswitch.consoleLog("notice", "[NORMALIZE CALLERID] Callerid is set based on company_caller_id: " .. callerid_prefix .. company_caller_id .. "\n")
        session:setVariable("effective_caller_id_name", callerid_prefix .. company_caller_id)
        session:setVariable("effective_caller_id_number", callerid_prefix .. company_caller_id)
        do return end
    end

    freeswitch.consoleLog("notice", "[NORMALIZE CALLERID] No action were preformed.\n")

end