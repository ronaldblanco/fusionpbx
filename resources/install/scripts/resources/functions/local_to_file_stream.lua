function local_to_file_stream(stream)

    if (string.find(stream, "local_stream://", 1, true)) then
        local api = freeswitch.API()
<<<<<<< HEAD
        freeswitch.consoleLog("NOTICE", "[local_to_file_stream] Got this stream - "..stream.."\n");
        local local_stream_list = api:executeString("local_stream show") -- Получаем все классы, как бонус - получаем пути к файлам.
        local current_stream = stream:gsub("local_stream://", "")
        local found_stream = ""
=======
	    freeswitch.consoleLog("NOTICE", "[local_to_file_stream] Got this stream - "..stream.."\n");
        local local_stream_list = api:executeString("local_stream show") -- Получаем все классы, как бонус - получаем пути к файлам.
        local current_stream = stream:gsub("local_stream://", "")
	    local found_stream = ""
>>>>>>> ee864995b18b0419500fca412947d3665ed2755f

        for line in string.gmatch(local_stream_list, "([^\n]+)") do -- Эзотерика разбития полученного списка по строчкам
            if (string.find(line, current_stream, 1, true)) then
                found_stream = line
                break
            end
        end
        freeswitch.consoleLog("NOTICE", "[local_to_file_stream] found this - "..found_stream.."\n");

        if (found_stream ~= "") then
            local class, location = found_stream:match("([^,]+),([^,]+)") -- Получаем путь к папке с файлами
            local files = io.popen('find "'..location..'" -type f | awk \'/wav/ || /mp3/\'') -- Получаем список файлов
            local files_string = ""

            for file in files:lines() do -- Собсно, лепим строчку для file_string
                files_string = files_string..file.."!"
            end
<<<<<<< HEAD
        files_string_orig = files_string
        while (files_string_orig..files_string):len() < 4000 do
            files_string = files_string..files_string_orig
        end
=======
		files_string_orig = files_string
		while (files_string_orig..files_string):len() < 4000 do
			files_string = files_string..files_string_orig
		end
>>>>>>> ee864995b18b0419500fca412947d3665ed2755f
            if (string.len(files_string) ~= 0) then
                files_string = "file_string://"..files_string:sub(1, #files_string - 1)
                return files_string
            end
         end
    end

    return stream
<<<<<<< HEAD
end
=======
end
>>>>>>> ee864995b18b0419500fca412947d3665ed2755f
