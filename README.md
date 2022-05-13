<div align="center">
<h1>Diwa: unofficiall Distrowatch API</h1>
</div>

# Demo 
[Demo!](https://diwa.herokuapp.com/api)

# Docs 
[Docs!](https://diwa.herokuapp.com/docs)

# Description
Diwa is an open source project and simple unofficail API from [Distrowatch](https://distrowatch.com/) site to get some public data of open source system operation like Linux, BSD, etc. for the routes, you can see it below.


# Routes lists
For now, Diwa has a more than 10 routes to get the current data of distribution, ranking, news, etc.

| Name API | Description | Route  | Note
| -------- | ----------- | ------ | ----|
| Index | Get all endpoints and info about this API | /  |-
| All Distribution | Get all Distribution | /api/distribution | -
| Distribution Detail | Get distribution information detail | /api/distribution/{name} <br><br> example: /api/distribution/mx | If {name} not found, will return 404
| Search | get specific distribution as you want | /api/params/search | -
| Search Params | Get all available parameters for search the distribution (above ↑) | /api/search?ostype={os_type}&category={distribution_category}&origin={country_of_origin}&basedon={based_on}&notbasedon={not_based_on}&desktop={desktop_environment}&architecture={architecture}&package={package_manager}&rolling={release_model}&isosize={install_media_size}&netinstall={install_mehthod}&language={multi_language_support}&defaultinit={software_init}&status={status} <br><br> example: /api/notbasedon=None&ostype=Linux&category=All&origin=All&basedon=Ubuntu&desktop=Xfce&architecture=All&package=All&rolling=All&isosize=All&netinstall=All&language=All&defaultinit=All&status=Active | If one of the {params} not found, distrowatch.com will used default params(All/None)
| Ranking(Default) | Get top 100 distribution ranking of last 6 months | /api/ranking |-
| Ranking(Custom) | Get top 100 distribution ranking but with parameter | /api/ranking/{slug} <br><br> example: /api/ranking/trending-1 <br><br> You can get all available parameters (below ↓) . | If {slug} not found, distrowatch.com will return the home page with default ranking(last 6 months). make sure {slug} is correct
| Ranking Params | Get all available parameters for Ranking(Custom) | /api/params/ranking | -
| All News(Default) | Get latest 12 news and 1 sponsor news | /api/news | -
| All News(Custom) | Get specific news | /api/news/filter/distribution={distribution}&release={release}&month={month}&year={year} <br><br> example: /api/news/filter/distribution=mx&release=stable&month=April&year=2021 <br><br> You can get all available parameters (below ↓)  | If one of the {params} not found, distrowatch.com will return the home page with default params(all). make sure all {params} are correct
| News Params | Get all available parameters for News(Custom) | /api/params/news | -
| News Detail | Get News information detail | /api/news/{news_id} <br><br> example: /api/news/11300 | If {news_id} not found, distrowatch.com will return the home page. make sure {news_id} is correct
| Weekly News Detail | Get weekly news information detail | /api/weekly/{weekly_id} <br><br> example: /api/weekly/20210719 | If {weekly_id} not found, distrowatch.com will return the latest weekly news. make sure {weekly_id} is correct
| All Weekly News | Get all weekly news | /api/weekly | Warning!, big size response

# Installation
If you want to add this project in your own machine, you can install this project by following the step below

Clone or download this repository
```shell
$ git clone https://github.com/Zzzul/diwa.git
```

Install all dependencies
```shell
# install laravel dependency
$ composer install
```

Generate app key, configure `.env` file.
```shell
# create copy of .env
$ cp .env.example .env

# create laravel key
$ php artisan key:generate

# Start Laravel local development server
$ php artisan serve
```

# Showcase
If you use this API to your project application, you can register your project in this showcase below :

# Contribution
Want to make this project better? You can contribute this project, I am very open if there are contributions to this project.

# License
MIT License.
