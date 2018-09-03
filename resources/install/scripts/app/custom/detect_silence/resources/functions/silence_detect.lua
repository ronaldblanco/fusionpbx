function silence_detect(file)
    if session:ready() then
        local file_duration = session:getVariable('record_ms')
        session:execute("wait_for_silence", "200 15 10 " .. record_ms .. " " .. file)
        session:execute("info")
    end
end