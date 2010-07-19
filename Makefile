VERSION := 0.1

simplejson:
	curl -O http://pypi.python.org/packages/source/s/simplejson/simplejson-2.1.1.tar.gz
	tar xzvf simplejson-2.1.1.tar.gz
	mv simplejson-2.1.1/simplejson .
	rm -r simplejson-2.1.1*

archive:
	mkdir -p $*
	tar czf --exclude $* --exclude .swp $*/csv2json-$(VERSION).tar.gz *

clean:
	rm -rf archive
