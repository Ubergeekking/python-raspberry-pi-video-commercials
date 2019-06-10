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
import re


def get_random_commercial():
	global drive
	now = datetime.datetime.now()
	month = now.month
	commercials_folder = "commercials"
	if month==12:
		commercials_folder = "xmas/" + commercials_folder
	video = glob.glob(os.path.join(drive + commercials_folder, '*.*'))
	if video:
		return random.choice(video)
	return None

def play_video(source, commercials, max_commercials_per_break):
	#os.system("killall -9 omxplayer");
	try:
		comm_source = get_random_commercial()
		comm_player = OMXPlayer(comm_source, args=['--no-osd', '--blank'], dbus_name="omxplayer.player1")
		comm_player.hide_video()
		comm_player.pause()
		comm_player.set_volume(-1)
		
		print('Main video file:', source)

		player = OMXPlayer(source, args=['--no-osd', '--blank'], dbus_name="omxplayer.player0")
		print('Boosting Volume by ' + str(get_volume(source)) + 'db')
		player.set_volume(get_volume(source))
		sleep(1)
		player.pause()
		player.play()
		lt = 0
		
		while (1):
			try:
				position = player.position()
			except:
				break
			
			
			t = math.floor(time.time())
			if t % 2 == 0:
				lt=t
			
			if len(commercials) > 0:
				#found a commercial break, play some commercials
				if math.floor(position)==commercials[0]:
					commercials.pop(0)
					player.hide_video()
					sleep(0.5)
					player.pause()				
					sleep(0.5)
					comm_i = max_commercials_per_break
					while(comm_i>=0):
						comm_source = get_random_commercial()
						print('Playing commercial #' + str(comm_i), comm_source)
						comm_player.load(comm_source)
						comm_player.pause()
						sleep(0.1)
						if comm_i==4:
							comm_player.show_video()
							
						comm_player.play()
						while (1):
							try:
								comm_position = math.floor(comm_player.position())
							except:
								break
						comm_i = comm_i - 1
						sleep(1)
					
					player.show_video()
					player.play()


		player.quit()
		#play some commercials after the video has played

		player.hide_video()
		sleep(0.5)
		player.pause()				
		sleep(0.5)
		comm_i = max_commercials_per_break
		while(comm_i>=0):
			comm_source = get_random_commercial()
			print('Playing commercial #' + str(comm_i), comm_source)
			comm_player.load(comm_source)
			comm_player.pause()
			sleep(1)
			if comm_i==4:
				comm_player.show_video()
			comm_player.play()
			while (1):
				try:
					a = math.floor(comm_player.position()), comm_player.is_playing()
				except:
					break
				comm_i = comm_i - 1
			sleep(1)
	except:
		print("error main")

	try:
		comm_player.quit()
	except:
		print("error comm quit")
	try:
		player.quit()
	except:
		print("error player quit")
	
	return

def get_commercials(source):
	if os.path.isfile(source + '.commercials') == True:
		with open(source + '.commercials') as temp_file:
			return [int(line.rstrip()) for line in temp_file]
	return []

def get_videos_from_dir(dir):
	results = glob.glob(os.path.join(dir, '*.mp4'))
	results.extend(glob.glob(os.path.join(dir, '*.avi')))
	results.extend(glob.glob(os.path.join(dir, '*.mkv')))
	results.extend(glob.glob(os.path.join(dir, '*.mkv')))
	
	return results

def get_volume(file_name):
	global volume_list
	file_name = os.path.basename(file_name).lower()
	for x, y in volume_list.items():
		if re.sub('[^A-Za-z0-9]+', '', file_name).find(re.sub('[^A-Za-z0-9]+', '', x.lower())) != -1:
			return y
	return 1

drive = '/mnt/content/'
volume_list = {}
volume_file = drive + 'volume.list'
if os.path.isfile(volume_file) == True:
	with open(volume_file) as temp_file:
		for line in temp_file:
			tmp_lst = line.rstrip().split('=')
			volume_list[tmp_lst[0]] = int(tmp_lst[1])

	
while(1):
	now = datetime.datetime.now()
	month = now.month
	h = now.hour
	m = now.minute

	folder = ""
	folder2 = ""
	dayfolder = ""

	d = datetime.datetime.today().weekday()
	
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
		folder = "old_reruns"
		folder2= "gameshows"
	elif h>=15 and h<17:
		folder = "cartoons"
	elif h==17:
		#news at 5
		folder = "news"
	elif h>=18 and h<20:
		folder = "new_reruns"
	elif h>= 20 and h<23:
		folder = "primetime" + dayfolder
	elif h==23:
		folder = "latenight"

	if month==12:
		#christmas programming
		folder = "xmas/" + folder
		folder2 = ""

	if d==6 and h>=4 and h<13:
		#sunday morning
		folder = "specials/sunday_morning"
	if d==5 and h>=7 and h<=9 and random.randint(1,9)==5:
		#saturday night, take a chance for a movie of the week!
		folder = "specials/movies"
	if d==5 and h>=2 and h<=3 and random.randint(1,5)==2 and month>=4 and month <=9:
		#saturday afternoon, in spring/summer, take a chance for a baseball game
		folder = "specials/baseball"
	
	video = get_videos_from_dir(drive + folder)
	
	print('Current Folder:', drive + folder)
	if folder2 != "":
		video = video + get_videos_from_dir(drive + folder2)
	
	if video:
		source = random.choice(video)
		commercials = get_commercials(source)
		play_video(source, commercials, 4)


# Commercials format time in seconds to interrupt. Each commercial break is on a new line
#
#	60
#	600
#	1200