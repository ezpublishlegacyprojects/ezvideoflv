<?php /*

[Convert]
AlwaysUseCronjob=false
# if the size of the video file is greater than
# UseCronjobSize Mo, use cronjob to convert it
# Write disabled to never use cronjob
UseCronjobSize=2

[Preview]
# jpeg for a JPEG file
# png for a PNG file
Format=jpeg
# middle or a number of frame
Frame=middle
Path=flv_preview

[Converter]
Program=ffmpeg
Options[]
Options[]=-y
# input file
Options[]=-i <original_file>
# quality
Options[]=-qmax 15
# sound
Options[]=-ar 22050 -ab 32 
# output file
Options[]=-f flv <converted_file>

[FLVPlayer]
File=flash/player_flv_maxi.swf
# See http://flv-player.net/players/maxi/documentation/
Options[]
Options[margin]=2
Options[showstop]=1
Options[showvolume]=1
Options[showtime]=2
Options[showplayer]=always
Options[showloading]=always


*/ ?>
