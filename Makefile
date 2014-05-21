all: clean run

run:
	php run.php

clean:
	rm -rf exported/
	mkdir exported/
	touch exported/.gitkeep