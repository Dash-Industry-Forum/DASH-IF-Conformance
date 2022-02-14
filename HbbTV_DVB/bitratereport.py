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
