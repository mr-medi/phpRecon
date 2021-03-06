# phpRecon

Collect information about a domain like HTML comments to get a possible hidden functionality, additional information about the backend, vulnerable forms, HTTP headers and use it to plan a attack vector

## Installation

```bash
#Clone the repo
git clone https://github.com/mr-medi/phpRecon.git
```

## Running the tests

``` php
<?php
	$domain = new Domain('http://mypage.com');
	echo $domain->getRobotsFile();
	echo $domain->getParsedDataScan();

```

### Break down into end to end tests


The first thing is to enter a URL, with this data the backend will make a GET request to obtain all the links belonging to the same domain, for each link obtained it will make another GET request to obtain the links of the same domain and save them in an array, Let's say you only get the url links entered up to a two layer level.

Let´s take an example:

I enter the url "http://mypage.com".
This url has a link:
"http://mypage.com/login".
In turn, the login page has the link "http://mypage.com/register" which will be saved correctly in our array but if this URL contains "http://mypage.com/forgot-password" we will not get it anymore that more than two layers will have passed from the entered URL.

Finally, we iterate through each URL in the generated array and save all the HTML comments, forms, HTTP headers on each page and
 retrieve the robots.txt file.

You can see the main page in the following image:
![Index page](https://github.com/mr-medi/phpRecon/blob/master/assets/images/page.png?raw=true)

To start, you just have to enter the URL in the input and wait until the results are shown.

## Authors

* **Mr.Medi**
