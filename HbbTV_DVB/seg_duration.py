#!/usr/bin/python
# This program is free software: you can redistribute it and/or modify
#  it under the terms of the GNU General Public License as published by
#  the Free Software Foundation, either version 3 of the License, or
#  (at your option) any later version.
#
#  This program is distributed in the hope that it will be useful,
#  but WITHOUT ANY WARRANTY; without even the implied warranty of
#  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#  GNU General Public License for more details.
#
#  You should have received a copy of the GNU General Public License
#  along with this program.  If not, see <http://www.gnu.org/licenses/>.

import sys, matplotlib
matplotlib.use('Agg')
import matplotlib.pyplot as plt
# Force matplotlib to not use any Xwindows backend.

segment_duration = [float(i) for i in sys.argv[1].split(',')]

min_val = min(segment_duration)
max_val = max(segment_duration)
avg_val = sum(segment_duration)/len(segment_duration)

index = list(range(len(segment_duration)))

plt.hist(segment_duration)
plt.ylabel('Frequency')
plt.xlabel('Duration (sec)')
plt.title('Segment duration histogram')

#Arrange for legend showing max, min and avg values of durations.
max, = plt.plot([], label='Max = '+str(format(max_val, '.2f'))+' sec')
min, = plt.plot([], label='Min = '+str(format(min_val, '.2f'))+' sec')
avg, = plt.plot([], label='Avg = '+str(format(avg_val, '.3f'))+' sec')

if (sys.argv[2] == 'Not_Set'):
	duration, = plt.plot([], label='MPD duration = '+sys.argv[2])
else:
	duration, = plt.plot([], label='MPD duration = '+sys.argv[2]+' sec')
#plt.legend(handles=[max, min, avg, duration], loc=0)
plt.legend()

plt.savefig(sys.argv[3])
