#!/usr/local/bin/python
"""
A simple script for generating JSON/JavaScript from comma-separated (or
otherwise delimited) values.

Python 2.7 or higher is recommended see :
"Floating Point Arithmetic issues and Limitations" at
http://docs.python.org/2/tutorial/floatingpoint.html

by Shawn Allen <shawn at stamen dot com>
modified by Alexandre Dube <adube at mapgears dot com> for lighter json
"""
import csv
try:
    import json
except ImportError:
    import simplejson as json
from StringIO import StringIO

# These are shorthands for delimiters that might be a pain to type or escape.
delimiter_map = {'tab': '\t',
                 'sc':  ';',
                 'bar': '|'}

def is_number(s):
    try:
        float(s)
        return True
    except ValueError:
        return False

def is_int(s):
    try:
        int(s)
        return True
    except ValueError:
        return False

def csv2json(csv_file, delimiter=',', quotechar='"', indent=None, callback=None, variable=None, **csv_opts):
    if delimiter_map.has_key(delimiter):
        delimiter = delimiter_map.get(delimiter)
    reader = csv.DictReader(csv_file, delimiter=delimiter, quotechar=quotechar or None, **csv_opts)

    # manually cast to integer or float according values for a lighter json
    # csv.DictReader has no mean to return unquoted integer and float values,
    # that's why it's manually done here. None really efficient upon script
    # execution, but json is much lighter that way
    rows = []
    for row in reader:
        for field in row:
            if is_number(row[field]):
                if is_int(row[field]):
                    row[field] = int(row[field])
                else:
                    row[field] = float(row[field])
        rows.append(row)

    if hasattr(indent, 'isdigit') and indent.isdigit():
        indent = ' ' * int(indent)
    out = StringIO()
    if callback:
        out.write('%s(' % callback);
    elif variable:
        out.write('var %s = ' % variable)
    json.dump(rows, out, indent=indent, separators=(',', ':'))
    if callback:
        out.write(');');
    elif variable:
        out.write(';')
    return out.getvalue()

if __name__ == '__main__':
    import sys
    from optparse import OptionParser

    parser = OptionParser()
    parser.add_option('-F', '--field-separator', dest='fs', default=',',
                      help='The CSV file field separator, default: %default')
    parser.add_option('-q', '--field-quote', dest='fq', default='"',
                      help='The CSV file field quote character, default: %default')
    parser.add_option('-i', '--indent', dest='indent', default=None,
                      help='The string with which to indent the output GeoJSON, '
                           'defaults to none.')
    parser.add_option('-p', '--callback', dest='callback', default=None,
                      help='The JSON-P callback function name.')
    parser.add_option('-v', '--variable', dest='var', default=None,
                      help='If provided, the output becomes a JavaScript statement'
                      ' which assigns the JSON structure to a variable of the same'
                      ' name.')
    options, args = parser.parse_args()

    close = False
    if len(args) > 0 and args[0] != '-':
        csv_file = open(args.pop(0), 'r')
        close = True
    else:
        csv_file = sys.stdin
    print csv2json(csv_file, options.fs, options.fq, options.indent, options.callback, options.var)
    if close:
        csv_file.close()
