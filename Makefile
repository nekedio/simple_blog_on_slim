start:
	php -S localhost:8080 -t public public/index.php 2>log.txt &

browser:
	w3m http://localhost:8080/

clear:
	rm log.txt
	rm errors.txt
