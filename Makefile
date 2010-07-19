VERSION := 0.1

archive:
	mkdir -p $*
	tar czf --exclude $* --exclude .swp $*/csv2json-$(VERSION).tar.gz *

clean:
	rm -rf archive
