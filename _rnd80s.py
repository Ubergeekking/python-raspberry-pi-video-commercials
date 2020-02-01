#!/usr/bin/python
from omxplayer import OMXPlayer
from time import sleep
import math
import os
from os import path
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
			if now.month>=12 and now.day>25:
				#it's past thanksgiving and also past xmas
				return False
			else:
				#past thanksgiving and is xmas or before
				return True
	return False

#def get_random_commercial():
#	global drive
	#now = datetime.datetime.now()
	#month = now.month
	#day = now.day
#	commercials_folder = "commercials"
#	commercials_folder2 = "commercials/local"
#	if PastThanksgiving(False): #play xmas commercials if we're at or past thanksgiving
#		commercials_folder = commercials_folder + "/xmas" 
#	video = get_videos_from_dir(drive + commercials_folder) + get_videos_from_dir(drive + commercials_folder2) 
#	if video:
#		return random.choice(video)
#	return None

#commercials_xmas = []
commercials_months = []
commercials_local = []
commercials_time = 0

def preload_commercials():
	global drive
	#global commercials_xmas
	global commercials_local
	global commercials_months
	global commercials_time

	#commercials_xmas = []
	commercials_months = []
	commercials_local = []
	
	commercials_time = time.time()
	#commercials_xmas = get_videos_from_dir(drive + "commercials/xmas")
	commercials_local = get_videos_from_dir(drive + "commercials/local")
	commercials_months.append([])
	for c in range(1,13):
		print("preloading commercials month " + str(c) + " (" + str(calendar.month_name[c]) + ")")
		commercials_months.append(get_videos_from_dir(drive + "commercials/" + calendar.month_name[c].lower()))

def get_random_commercial():
	#if PastThanksgiving(False): #play xmas commercials if we're at or past thanksgiving
	#	global commercials_months
	#	return random.choice(commercials_months[12])

	global commercials_local
	global commercials_months

	global now
	month = now.month
	
	if commercials_months:
		if random.randint(1,2)<2:
			#higher chance to see commercials from the current month, except xmas
			print("Choosing from this month: " + str(month))
			return random.choice(commercials_months[month])
		else:
			if random.randint(0,5)<2:
				#higher chance to see local commercial
				return random.choice(commercials_local)
			else:
				tmp_lst=[]
				#exclude december from commercial pool
				for c in range(1,12):
					if c!=month:
						tmp_lst = tmp_lst + commercials_months[c]
				
				return random.choice(tmp_lst)
	return None

next_video_to_play = None

def check_video():
	global next_video_to_play
	if path.exists("next.video"):
		file = open("next.video", "r") 
		contents=file.read() 
		file.close()
		os.remove("next.video")
		next_video_to_play = contents
		print("Outside source has requested a video: " + next_video_to_play)
		return True
	return False

last_video_played = ""

def play_video(source, commercials, max_commercials_per_break):
	if source==None:
		return
	#os.system("killall -9 omxplayer");
	try:
		global next_video_to_play
		global last_video_played
		
		err_pos = -1.0
		if next_video_to_play=='':
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
		
		next_video_to_play=''
		
		err_pos = 0.0

		comm_source = get_random_commercial()
		err_pos = 0.1
		comm_player = OMXPlayer(comm_source, args=['--no-osd', '--blank'], dbus_name="omxplayer.player1")
		err_pos = 0.12
		comm_player.set_aspect_mode('stretch');
		err_pos = 0.13
		comm_player.hide_video()
		err_pos = 0.14
		comm_player.pause()
		err_pos = 0.15
		comm_player.set_volume(1)
		err_pos = 0.2
		
		print('Main video file:' + source)
		err_pos = 0.21
		contents = urllib2.urlopen("http://127.0.0.1/?current_video=" + urllib.quote_plus(source)).read()
		err_pos = 0.23
		player = OMXPlayer(source, args=['--no-osd', '--blank'], dbus_name="omxplayer.player0")
		err_pos = 0.3
		print('Boosting Volume by ' + str(get_volume(source)) + 'db')
		err_pos = 0.31
		player.set_aspect_mode('stretch');
		err_pos = 0.32
		player.set_volume(get_volume(source))
		err_pos = 0.33
		sleep(1)
		err_pos = 1.0
		player.pause()
		err_pos = 1.1
		strSubtitles = player.list_subtitles()
		if(strSubtitles):
			player.hide_subtitles()
		err_pos = 1.2
		player.play()
		err_pos = 1.3
		lt = 0
		while (1):
			err_pos = 2.0
			try:
				position = player.position()
			except:
				break

			if check_video()==True:
				#check if an outside source wants to play a new video
				print('outside source video return')
				err_pos = 8.0
				player.hide_video()
				player.quit()
				comm_player.hide_video()
				comm_player.quit()
				sleep(0.5)
				return
			
			if len(commercials) > 0:
				#found a commercial break, play some commercials
				if math.floor(position)==commercials[0]:
					commercials.pop(0)
					player.hide_video()
					player.pause()
					sleep(0.5)
					comm_i = max_commercials_per_break
					err_pos = 3.0
					while(comm_i>=0):
						if check_video()==True or next_video_to_play!='':
							#check if an outside source wants to play a new video
							print('outside source video return')
							err_pos = 6.0
							player.hide_video()
							player.quit()
							comm_player.hide_video()
							comm_player.quit()
							sleep(0.5)
							return
					
						comm_source = get_random_commercial()
						print('Playing commercial #' + str(comm_i), comm_source)
						contents = urllib2.urlopen("http://127.0.0.1/?current_comm=" + urllib.quote_plus(comm_source)).read()
						comm_player.load(comm_source)
						comm_player.pause()
						sleep(0.1)
						if comm_i==4:
							comm_player.show_video()
							
						comm_player.play()
						err_pos = 4.0
						while (1):
							try:
								comm_position = math.floor(comm_player.position())
							except:
								break
						comm_i = comm_i - 1
						sleep(1)
					
					err_pos = 5.0
					player.show_video()
					player.play()

		err_pos = 7.0
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
	#load single file for all commercials
	#usefull when commercial breaks are easily identifiable for a lot of episodes from a single show
	#first 8 chrs need to exactly the same
	if os.path.isfile(source[:8] + '.commercials.master') == True:
		with open(source[:8] + '.commercials.master') as temp_file:
			return [int(line.rstrip()) for line in temp_file]
	
	if os.path.isfile(source + '.commercials') == True:
		with open(source + '.commercials') as temp_file:
			return [int(line.rstrip()) for line in temp_file]
	return []

def get_videos_from_dir(dir):
	results = glob.glob(os.path.join(dir, '*.mp4'))
	results.extend(glob.glob(os.path.join(dir, '*.avi')))
	results.extend(glob.glob(os.path.join(dir, '*.mkv')))
	results.extend(glob.glob(os.path.join(dir, '*.mov')))
	results.extend(glob.glob(os.path.join(dir, '*.flv')))
	results.extend(glob.glob(os.path.join(dir, '*.wmv')))
	
	return results

def get_volume(file_name):
	global volume_list
	global volume_file
	
	if os.path.isfile(volume_file) == True:
		with open(volume_file) as temp_file:
			for line in temp_file:
				tmp_lst = line.rstrip().split('=')
				volume_list[tmp_lst[0]] = int(tmp_lst[1])
	
	file_name = os.path.basename(file_name).lower()
	for x, y in volume_list.items():
		if re.sub('[^A-Za-z0-9]+', '', file_name).find(re.sub('[^A-Za-z0-9]+', '', x.lower())) != -1:
			return y
	return 2

def play_some_commercials(max_commercials_per_break):
	try:
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
	except:
		print("Error playing commercial from function")

now = datetime.datetime.now()
drive = '/media/pi/750gb/'

preload_commercials()

volume_list = {}
volume_file = drive + 'volume.list'
if os.path.isfile(volume_file) == True:
	with open(volume_file) as temp_file:
		for line in temp_file:
			tmp_lst = line.rstrip().split('=')
			volume_list[tmp_lst[0]] = int(tmp_lst[1])

while(1):
	now = datetime.datetime.now()

	if os.path.isfile(drive + 'test.date') == True:
		with open(drive + 'test.date', 'r') as file:
			now = datetime.datetime.strptime(file.read(), '%b %d %Y %I:%M%p')
		print("Using Test Date Time " + str(now))

	month = now.month
	h = now.hour
	m = now.minute
	
	folder = ""
	folder2 = ""
	dayfolder = ""

	d = now.weekday()
	ddm = now.day
	
	print("Date: Month: " + str(month) + " Hour: " + str(h) + " Minute: " + str(m) + " Weekday: " + str(d) + " Day: " + str(ddm))
	
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

	

	if d==5 and h>=19 and h<=21 and random.randint(1,9)==5:
		#saturday night, take a chance for a movie of the week!
		folder = "specials/movies"
		folder2 = ""
	if d==5 and h>=14 and h<=15 and random.randint(1,5)==2 and month>=4 and month <=9:
		#saturday afternoon, in spring/summer, take a chance for a baseball game
		folder = "specials/baseball"
		folder2 = ""
	if d==6 and h>=14 and h<=15 and random.randint(1,5)==2 and month>=9 and month<=2:
		#sunday afternoon, in fall/winter, take a chance for a football game
		folder = "specials/football"
		folder2 = ""


	#holiday specials
	if month==7 and ddm==4 and h>= 20 and h<22:
		#4th of July
		folder = "specials/4th of july"
		folder2 = ""
	if month==10 and ddm==31 and h>= 20 and h<23:
		#Halloween
		folder = "specials/halloween"
		folder2 = ""

	#fill space close to the hour
	print("Minute: " + str(m))
	#if (m>=24 and m<=29) or (m>=54 and m<=59):
		#close to half/top hour
		#folder = "specials/shorts"
	#	folder = ""
	#	folder2 = ""
	#if (m>=16 and m<=23) or (m>=46 and m<=53):
		#not quite close to half/top hour
	#	folder = "specials/long_shorts"
	#	folder2 = ""


	#special shows based on day and time
	if d==6 and h>=5 and h<10:
		#sunday morning
		folder = "specials/sunday_morning"
		folder2 = ""
	if (h==6 and m>=45) or (h==7 and m<=30):
		#play mr wizard in the morning
		folder = "cartoons/mr wizard"
		folder2 = ""

	if PastThanksgiving(False):
		#christmas programming
		folder = "specials/xmas"
		folder2 = ""
		
	if PastThanksgiving(True):
		#it's thankgiving day
		folder = "specials/thanksgiving" #pick random thanksgiving episodes
		folder2 = ""
		#check time to play parade and then football game
		if h>=9 and h<11: #play parade
			folder = "specials/thanksgiving/parades"
			folder2 = ""
		elif h>=11 and h<14: #play football
			folder = "specials/thanksgiving/football"
			folder2 = ""

	print('Current Folder: ' + folder)
	print('Secondary Folder: ' + folder2)
	if folder!="" or folder2!="": #folders have been set, load and play video
		video = get_videos_from_dir(drive + folder)
		
		print('Drive and Folder: ' + drive + folder)
		if folder2 != "":
			video = video + get_videos_from_dir(drive + folder2)


		if next_video_to_play != '':
			play_video(next_video_to_play, [], 0)
			next_video_to_play=''
		elif video:
			if time.time() - commercials_time > 1296000:
				preload_commercials()
			#select random video
			source = random.choice(video)
			
			#load commercial break time stamps for this video (if any)
			commercials = get_commercials(source)
			#play the video with commercial breaks
			if source==None:
				print("ERROR NO SOURCE!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!")
			play_video(source, commercials, 4)
			#play a couple commercials in between programs
			#play_some_commercials(2)
	else:
		#play_some_commercials(1)
		print('no folder')


# Commercials format time in seconds to interrupt. Each commercial break is on a new line
#
#	60
#	600
#	1200