#!/usr/bin/python
from omxplayer import OMXPlayer
from time import sleep
import math
import os
import glob
import random
import time
import datetime
import urllib2
import urllib
import re
import calendar

def PastThanksgiving(is_thanksgiving):
	global now # = datetime.date.today()
	daysInMonth = calendar.monthrange(now.year, 11)[1]
	dt = datetime.date(now.year, 11, daysInMonth)

	# Back up to the most recent Thursday
	offset = 4 - dt.isoweekday()
	if offset > 0: offset -= 7						  # Back up one week if necessary
	dt += datetime.timedelta(offset)					# dt is now date of last Th in month

	if(is_thanksgiving==True): #check if today is thanksgiving
		if now.date()==dt:
			return True
	else:
		if now.date() >= dt:
			return True

	return False

def get_random_commercial():
	global drive
	#now = datetime.datetime.now()
	#month = now.month
	#day = now.day
	commercials_folder = "commercials"
	commercials_folder2 = "commercials/local"
	if PastThanksgiving(False): #play xmas commercials if we're at or past thanksgiving
		commercials_folder = commercials_folder + "/xmas" 
	video = get_videos_from_dir(drive + commercials_folder) + get_videos_from_dir(drive + commercials_folder2) 
	if video:
		random.seed(time.time())
		return random.choice(video)
	return None

last_video_played = ""

def play_video(source, commercials, max_commercials_per_break):
	#os.system("killall -9 omxplayer");
	try:
		global last_video_played
		
		err_pos = -1
		print("Last video played: " + last_video_played)
		urlcontents = urllib2.urlopen("http://127.0.0.1/?getshowname=" + urllib.quote_plus(source)).read()
		print("Response: ")
		print(urlcontents)
		acontents = urlcontents.split("|")
		#if last_video_played == contents and contents!="News":
		if acontents[1]!="0" and acontents[0]!="News":
			print("Just played this show, skipping")
			return
		last_video_played = acontents[0]
		print("Last video played: " + last_video_played)
		
		err_pos = 0

		comm_source = get_random_commercial()
		comm_player = OMXPlayer(comm_source, args=['--no-osd', '--blank'], dbus_name="omxplayer.player1")
		comm_player.set_aspect_mode('stretch');
		comm_player.hide_video()
		comm_player.pause()
		comm_player.set_volume(0)
		
		print('Main video file:' + source)
		contents = urllib2.urlopen("http://127.0.0.1/?current_video=" + urllib.quote_plus(source)).read()
		player = OMXPlayer(source, args=['--no-osd', '--blank'], dbus_name="omxplayer.player0")
		print('Boosting Volume by ' + str(get_volume(source)) + 'db')
		player.set_aspect_mode('stretch');
		player.set_volume(get_volume(source))
		sleep(1)
		err_pos = 1
		player.pause()
		player.play()
		lt = 0
		while (1):
			err_pos = 2
			try:
				position = player.position()
			except:
				break
			
			if len(commercials) > 0:
				#found a commercial break, play some commercials
				if math.floor(position)==commercials[0]:
					commercials.pop(0)
					player.hide_video()
					player.pause()
					sleep(0.5)
					comm_i = max_commercials_per_break
					err_pos = 3
					while(comm_i>=0):
						comm_source = get_random_commercial()
						print('Playing commercial #' + str(comm_i), comm_source)
						contents = urllib2.urlopen("http://127.0.0.1/?current_comm=" + urllib.quote_plus(comm_source)).read()
						comm_player.load(comm_source)
						comm_player.pause()
						sleep(0.1)
						if comm_i==4:
							comm_player.show_video()
							
						comm_player.play()
						err_pos = 4
						while (1):
							try:
								comm_position = math.floor(comm_player.position())
							except:
								break
						comm_i = comm_i - 1
						sleep(1)
					
					err_pos = 5
					player.show_video()
					player.play()

		err_pos = 6
		player.hide_video()
		player.quit()
		sleep(0.5)
	except Exception as e:
		contents = urllib2.urlopen("http://127.0.0.1/?error=MAIN_" + str(err_pos) + "_" + urllib.quote_plus(str(e))).read()
		print("error main " + str(e))

	try:
		comm_player.quit()
	except Exception as ex:
		contents = urllib2.urlopen("http://127.0.0.1/?error=COMMERCIAL_" + str(err_pos) + "_" + urllib.quote_plus(str(ex))).read()
		print("error comm quit " + str(ex))
	try:
		player.quit()
	except Exception as exx:
		contents = urllib2.urlopen("http://127.0.0.1/?error=PLAYER_" + str(err_pos) + "_" + urllib.quote_plus(str(exx))).read()
		print("error player quit " + str(exx))
	
	return

def get_commercials(source):
	if os.path.isfile(source + '.commercials') == True:
		with open(source + '.commercials') as temp_file:
			return [int(line.rstrip()) for line in temp_file]
	return []

def get_videos_from_dir(dir):
	results = glob.glob(os.path.join(dir, '*.mp4'))
	
	#results.extend(glob.glob(os.path.join(dir, '*.avi')))
	#results.extend(glob.glob(os.path.join(dir, '*.mkv')))
	#results.extend(glob.glob(os.path.join(dir, '*.mov')))
	#results.extend(glob.glob(os.path.join(dir, '*.flv')))
	#results.extend(glob.glob(os.path.join(dir, '*.wmv')))
	
	return results

def get_volume(file_name):
	global volume_list
	file_name = os.path.basename(file_name).lower()
	for x, y in volume_list.items():
		if re.sub('[^A-Za-z0-9]+', '', file_name).find(re.sub('[^A-Za-z0-9]+', '', x.lower())) != -1:
			return y
	return 1

def play_some_commercials(max_commercials_per_break):
	comm_source = get_random_commercial()
	comm_player = OMXPlayer(comm_source, args=['--no-osd', '--blank'], dbus_name="omxplayer.player1")
	comm_player.set_aspect_mode('stretch');
	comm_player.hide_video()
	comm_player.pause()
	comm_player.set_volume(-1)
	comm_i = max_commercials_per_break
	while(comm_i>=0):
		comm_source = get_random_commercial()
		print('Playing commercial #' + str(comm_i), comm_source)
		contents = urllib2.urlopen("http://127.0.0.1/?current_comm=" + urllib.quote_plus(comm_source)).read()
		comm_player.load(comm_source)
		comm_player.pause()
		sleep(0.1)
		if comm_i==4:
			comm_player.show_video()
			
		comm_player.play()
		err_pos = 4
		while (1):
			try:
				comm_position = math.floor(comm_player.position())
			except:
				break
		comm_i = comm_i - 1
		sleep(1)


now = datetime.datetime.now()
drive = '/mnt/content/'

def loadVolumeList():
	volume_file = drive + 'volume.list'
	vlist = {}
	if os.path.isfile(volume_file) == True:
		with open(volume_file) as temp_file:
			for line in temp_file:
				tmp_lst = line.rstrip().split('=')
				vlist[tmp_lst[0]] = int(tmp_lst[1])
	
	return vlist

volume_list = loadVolumeList()

while(1):
	
	now = datetime.datetime.now()

	if os.path.isfile(drive + 'test.date') == True:
		with open(drive + 'test.date', 'r') as file:
			now = datetime.datetime.strptime(file.read(), '%b %d %Y %I:%M%p')
		print("Using Test Date Time " + str(now))

	month = now.month
	h = now.hour
	m = now.minute
	print(str(month) + " " + str(h) + " " + str(m))
	folder = ""
	folder2 = ""
	dayfolder = ""

	d = now.weekday()
	random.seed(time.time())
	print("finding day")
	if d==0: #monday
		dayfolder += "/monday/"
	elif d==1: #tuesday
		dayfolder += "/tuesday/"
	elif d==2: #wedsnesday
		dayfolder += "/wedsnesday/"
	elif d==3: #thursday
		dayfolder += "/thursday/"
	elif d==4: #friday
		dayfolder += "/friday/"
	elif d==5: #saturday
		dayfolder += "/saturday/"
	elif d==6: #sunday
		dayfolder += "/sunday/"

	if d==5 and h>=4 and h<2:
		#saturday morning cartoons
		folder = "cartoons"
	elif h>=0 and h<4:
		folder = "movies"
	elif h>=4 and h<10:
		folder = "cartoons"
	elif h>=10 and h<15:
		folder = "reruns"
		folder2= "gameshows"
	elif h>=15 and h<17:
		folder = "cartoons"
	#elif h==17:
		#news at 5
	#	folder = "news"
	elif h>=17 and h<20:
		folder = "reruns"
	elif h>= 20 and h<23:
		folder = "primetime" + dayfolder
		if random.randint(1,14) == 1:
			#1 in 14 chance to play a random video or primetime special
			folder = "primetime/random"
	elif h==23 and (d>=0 and d<=4):
		#latenight monday through friday
		folder = "latenight"
	elif h==23 and d==5:
		#saturday night
		folder = "latenight/snl"
	elif h==23 and d==6:
		#sunday night
		folder = "latenight"
	else:
		#just in case
		folder = "cartoons"

	if d==6 and h>=5 and h<10:
		#sunday morning
		folder = "specials/sunday_morning"
	if d==5 and h>=19 and h<=21 and random.randint(1,9)==5:
		#saturday night, take a chance for a movie of the week!
		folder = "specials/movies"
	if d==5 and h>=14 and h<=15 and random.randint(1,5)==2 and month>=4 and month <=9:
		#saturday afternoon, in spring/summer, take a chance for a baseball game
		folder = "specials/baseball"

	print("finding xmas day")
	if PastThanksgiving(False):
		#christmas programming
		folder = "xmas"
		folder2 = ""
		
	print("finding thanksgiving day")
	if PastThanksgiving(True):
		#it's thankgiving day
		folder = "thanksgiving" #pick random thanksgiving episodes
		#check time to play parade and then football game
		if h>=9 and h<11: #play parade
			folder = "thanksgiving/parades"
		elif h>=11 and h<14: #play football
			folder = "thanksgiving/football"

	print("getting videos from folder")
	video = get_videos_from_dir(drive + folder)
	
	print('Current Folder: ' + drive + folder)
	if folder2 != "":
		video = video + get_videos_from_dir(drive + folder2)
	
	if video:
		#select random video
		print("selecting random video")
		random.seed(time.time())
		source = random.choice(video)
		#load commercial break time stamps for this video (if any)
		commercials = get_commercials(source)
		#play the video with commercial breaks
		play_video(source, commercials, 4)
		#play a couple commercials in between programs
		#play_some_commercials(2)


# Commercials format time in seconds to interrupt. Each commercial break is on a new line
#
#	60
#	600
#	1200