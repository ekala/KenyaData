import urllib2, urllib, string, simplejson, json, csv

"""
Performs Geo-coding of the data
"""
def geoCode(fileName):
	# URL for fetching the geocodes
	geoCodeUrl = "http://maps.googleapis.com/maps/api/geocode/json?&%s&sensor=false"
	
	csvReader = csv.reader(open(fileName, 'rb'), delimiter=',', quotechar='"')
	
	# Iterate each item and geocode
	for item in csvReader:
		# Build Params - polling station{7}, polling area{5}, constituency{2} and region{0}
		paramString = ""
		
		# Set the name of the polling station
		if len(item) >= 7 and len(item[7]) > 0:
			paramString += "%s" %item[7]
			
		# Set the constituency
		if len(item[2]) > 0:
			paramString += ",%s" %item[2]
		
		# Set the region/province name
		if len(item[0]) > 0:
			paramString += ",%s,"%item[0]
		
		# Append the country name as the last parameter
		paramString += " Kenya"
		
		# Encode paramaeters for submission via HTTP GET
		encodedParams = urllib.urlencode({'address': paramString})
		
		# Issue request
		
		# 
		# Notes:
		# TODO: If the geocode does not return any results, fine tune the search
		#  so as to use more parameters and/or ping other geocoding services
		# 
		try:
			# Generat URL to be submitted
			url = geoCodeUrl % encodedParams.replace("%2C", ",")
			
			# Extract location info from the JSON response
			if extractLocationInfo(simplejson.load(urllib2.urlopen(url))):
				print "Geocoded site %s" % item[7]
			else:
				print "The request for %s : %s requries tuning" %(item[7], url)
				
		except urllib2.URLError, e:
			# Exception handling
			print "Error %d: %s" %(e.args[0], e.args[1])
	
	# Close the cursor
	cursor.close()


"""
Extracts the lat,lon information from the json response
Checks if any data has been returned first
"""
def extractLocationInfo(jsonData):
	# Only update when there's a response
	if len(jsonData["results"]) > 0:
		# Get the location data
		locationData = jsonData["results"][0]["geometry"]["location"]
		
		print "Gecoding results :%s " %locationData
		
		# TODO: Push the geocoded data to a database/csv or something
		return True

	print jsonData;
		
	return False
	
geoCode("streamed.txt")

