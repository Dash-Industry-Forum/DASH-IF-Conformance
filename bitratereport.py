#!/usr/bin/python
import matplotlib
# Force matplotlib to not use any Xwindows backend.
matplotlib.use('Agg')
import matplotlib.pyplot as plt
import sys

bitrates = [float(i) for i in sys.argv[1].split(',')]

min_val = min(bitrates)
max_val = max(bitrates)
avg_val = sum(bitrates)/len(bitrates)

index = list(range(len(bitrates)))

plt.hist(bitrates)
plt.ylabel('Frequency')
plt.xlabel('Bitrate (bps)')
plt.title('Segment bitrate histogram')

#Arrange for legend showing max, min and avg values of bitrates.
max, = plt.plot([], label='Max ='+str(format(max_val,'.2f'))+' bps')
min, = plt.plot([], label='Min ='+str(format(min_val,'.2f'))+' bps')
avg, = plt.plot([], label='Avg ='+str(format(avg_val,'.2f'))+' bps')
bandwidth,=plt.plot([], label='Rep bitrate ='+sys.argv[2]+' bps')
#plt.legend(handles=[max, min, avg,bandwidth], loc=0)
plt.legend()
plt.savefig(sys.argv[3])
