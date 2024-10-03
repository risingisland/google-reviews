<?php 
	/*
		Configuration.
		Note: You'll need a Google maps API key - requires both Maps JS and Places API services enabled.
	*/

	$place_ID		= ''; // Get from: https://developers.google.com/maps/documentation/javascript/examples/places-placeid-finder
	$api_key		= ''; // Google Maps API Key

	$min_star		= '4'; // The minimum star rating (min = 1)
	$max_rows		= '5'; // The maximum number of results (max = 5)
	$max_length		= '350'; // The maximum review length
?>

<link rel="stylesheet" id="google-reviews-css" href="google-reviews.css" type="text/css" media="all" />

<script src="https://code.jquery.com/jquery-2.2.4.min.js"></script>
<script src="https://maps.googleapis.com/maps/api/js?v=3.exp&key=<?php echo $api_key; ?>&libraries=places"></script>

<script>
(function($) {

	$.googlePlaces = function(element, options) {

		var defaults = {
			placeId:		'<?php echo $place_ID; ?>', 
			render:			['reviews'], 
			min_rating:		<?php echo $min_star; ?>, 
			max_rows:		<?php echo $max_rows; ?>, 
			max_length:		<?php echo $max_length; ?>, 
			rotateTime:		false
		};

		var plugin = this;

		plugin.settings = {}

		var $element = $(element),
		element = element;

		plugin.init = function() {
			plugin.settings = $.extend({}, defaults, options);
			$element.html("<div id='the-reviews'></div>"); // create a plug for google to load data into
			initialize_place(function(place){
				plugin.place_data = place;
				// render specified sections
				if(plugin.settings.render.indexOf('reviews') > -1){
					renderReviews(plugin.place_data.reviews);
					if(!!plugin.settings.rotateTime) {
						initRotation();
					}
				}
			});
        }

		var initialize_place = function(c){
			var map = new google.maps.Map(document.getElementById('the-reviews'));

			var request = {
				placeId: plugin.settings.placeId
			};

			var service = new google.maps.places.PlacesService(map);

			service.getDetails(request, function(place, status) {
				if (status == google.maps.places.PlacesServiceStatus.OK) {
					c(place);
				}
			});
		}

		var sort_by_date = function(ray) {
			ray.sort(function(a, b){
				var keyA = new Date(a.time),
				keyB = new Date(b.time);
				// Compare the 2 dates
				if(keyA < keyB) return -1;
				if(keyA > keyB) return 1;
				return 0;
			});
			return ray;
		}

		var filter_minimum_rating = function(reviews){
			for (var i = reviews.length -1; i >= 0; i--) {
				if(reviews[i].rating < plugin.settings.min_rating){
					reviews.splice(i,1);
				}
			}
			return reviews;
		}

		var renderReviews = function(reviews) {
			reviews = sort_by_date(reviews);
			reviews = filter_minimum_rating(reviews);
			var html = "";
			var row_count = (plugin.settings.max_rows > 0)? plugin.settings.max_rows - 1 : reviews.length - 1;
			// make sure the row_count is not greater than available records
			row_count = (row_count > reviews.length-1)? reviews.length -1 : row_count;
			for (var i = row_count; i >= 0; i--) {
				var stars = renderStars(reviews[i].rating);
				var date = convertTime(reviews[i].time);
				var maxLength = <?php echo $max_length; ?>;
				var reviewText = reviews[i].text;
				if (reviewText.length > maxLength) {
					reviewText = reviewText.substring(0, maxLength) + ' ...';
				}
				html = html+"<div class='review-item' itemprop='review' itemscope itemtype='http://schema.org/Review'><img src='"+reviews[i].profile_photo_url+"'/><div class='review-inner'><p class='review-text' itemprop='description'>"+reviewText+"</p><div class='review-meta'><span class='review-author' itemprop='author'>"+reviews[i].author_name+"</span><span class='review-sep'>, </span><span class='review-date' itemprop='datePublished'>"+date+"</span></div>"+stars+" "+googleIcon+"</div></div>"
			};
			$element.append(html);
		}
        
		var initRotation = function() {
			var $reviewEls = $element.children('.review-item');
			var currentIdx = $reviewEls.length > 0 ? 0 : false;
			$reviewEls.hide();
			if(currentIdx !== false) {
				$($reviewEls[currentIdx]).show();
				setInterval(function(){
					if(++currentIdx >= $reviewEls.length) {
						currentIdx = 0;
					}
					$reviewEls.hide();
					$($reviewEls[currentIdx]).fadeIn('slow');
				}, plugin.settings.rotateTime);
			}
        }

		var googleIcon = "<div class='google-icon'><svg xmlns='http://www.w3.org/2000/svg' id='google-icon' viewBox='0 0 48 48'><path fill='#ffc107' d='M43.611 20.083H42V20H24v8h11.303c-1.649 4.657-6.08 8-11.303 8c-6.627 0-12-5.373-12-12s5.373-12 12-12c3.059 0 5.842 1.154 7.961 3.039l5.657-5.657C34.046 6.053 29.268 4 24 4C12.955 4 4 12.955 4 24s8.955 20 20 20s20-8.955 20-20c0-1.341-.138-2.65-.389-3.917'/><path fill='#ff3d00' d='m6.306 14.691l6.571 4.819C14.655 15.108 18.961 12 24 12c3.059 0 5.842 1.154 7.961 3.039l5.657-5.657C34.046 6.053 29.268 4 24 4C16.318 4 9.656 8.337 6.306 14.691'/><path fill='#4caf50' d='M24 44c5.166 0 9.86-1.977 13.409-5.192l-6.19-5.238A11.9 11.9 0 0 1 24 36c-5.202 0-9.619-3.317-11.283-7.946l-6.522 5.025C9.505 39.556 16.227 44 24 44'/><path fill='#1976d2' d='M43.611 20.083H42V20H24v8h11.303a12.04 12.04 0 0 1-4.087 5.571l.003-.002l6.19 5.238C36.971 39.205 44 34 44 24c0-1.341-.138-2.65-.389-3.917'/></svg></div>"
			
		var renderStars = function(rating) {
			var stars = "<div class='review-stars' itemprop='reviewRating' itemscope itemtype='http://schema.org/Rating'><meta itemprop='worstRating' content='1'/><meta itemprop='ratingValue' content='" + rating + "'/><meta itemprop='bestRating' content='5'/><ul>";

			// fill in gold stars
			for (var i = 0; i < rating; i++) {
				stars = stars+"<li class='star'>&#9733;</li>";
			};

			// fill in empty stars
			if(rating < 5){
				for (var i = 0; i < (5 - rating); i++) {
					stars = stars+"<li class='star inactive'>&#9733;</li>";
				};
			}
			stars = stars+"</ul></div>";
			return stars;
		}

		var convertTime = function(UNIX_timestamp) {
			var a = new Date(UNIX_timestamp * 1000);
			var months_En = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
			var months_Es = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
			var months_Num = ['01','02','03','04','05','06','07','08','09','10','11','12'];
			var time_US = months_En[a.getMonth()] + ' ' + a.getDate() + ', ' + a.getFullYear();
			var time_ES = a.getDate() + ' de ' + months_Es[a.getMonth()] + ', ' + a.getFullYear();
			var time_EU = a.getFullYear() + '.' + months_Num[a.getMonth()] + '.' + a.getDate();

			return time_ES; // Choose date format
		}

		plugin.init();

	}

	$.fn.googlePlaces = function(options) {
        return this.each(function() {
			if (undefined == $(this).data('googlePlaces')) {
				var plugin = new $.googlePlaces(this, options);
				$(this).data('googlePlaces', plugin);
			}
		});
	}

})(jQuery);
</script>

<div id="google-reviews"></div>

<script>
	$(document).ready(function() {
		$("#google-reviews").googlePlaces({});
	});
</script>
