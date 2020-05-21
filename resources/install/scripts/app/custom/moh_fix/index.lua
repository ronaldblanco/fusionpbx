-- modify MOH from local_stream to file_stream
require "app.custom.moh_fix.resources.functions.local_to_file_stream"

function fsLog(message)
    if opts.v then
        freeswitch.consoleLog("NOTICE", "[MOH FIX] " .. message .. "\n")
    end
end


opthelp = [[
 -s, --sort-mode                    Sort Mode. As of now only Random is supported. By default - no sort
 -v, --verbose                      If specified - verbose info is printed on FS console
 -t, --transfer-ringback=OPTARG     True/False. Default - True. Set also transfer ringback
 -r, --ringback=OPTARG              True/False. Default - True. Set also transfer ringback
]]


opts, args, err = require('app.custom.functions.optargs').from_opthelp(opthelp, argv)

if opts == nil then
    freeswitch.consoleLog("ERROR", "[MOH FIX] Options are not parsable " .. err)
    do return end
end

if (session:ready()) then

    -- Check already fixed
    local moh_fix = session:getVariable("moh_fix")
    if (moh_fix and moh_fix == 'true') then
        fsLog("MOH is fixed already")
        do return end
    end

    hold_music = session:getVariable("hold_music")
    
    if (hold_music ~= nil) then
        hold_music = local_to_file_stream(hold_music, opts.s)
        session:execute("export", "hold_music=" .. hold_music)
    end
    fsLog("hold_music set")

    if (opts.t == nil or opts.t == 'true') then
        transfer_ringback = session:getVariable("transfer_ringback")
        if (transfer_ringback ~= nil) then
            transfer_ringback = local_to_file_stream(transfer_ringback, opts.s)
            session:execute("export", "transfer_ringback=" .. transfer_ringback)
        end
        fsLog("transfer_ringback set")
    end

    if (opts.r == nil or opts.r == 'true') then
        ringback = session:getVariable("ringback")
        if (ringback ~= nil) then
            ringback = local_to_file_stream(ringback, opts.s)
            session:execute("export", "ringback=" .. ringback)
        end
        fsLog("ringback set")
    end

    -- Prevent double call of script
    session:setVariable("moh_fix", "true")
end
