function changeMoodText(moodtext) // change mood-text on click
{
    var sz = document.forms['next'].elements['moods'];

    for (var i=0, len=sz.length; i<len; i++) 
    {
        // mood buttons
        sz[i].onclick = function()
        {
        var text = document.getElementById("titlep");
        if (this.value == "favorite")
            text.innerHTML = moodtext + "like listening to my favorite songs";
        else
            text.innerHTML = moodtext + this.value;
        };
    }
}

// This code loads the IFrame Player API code asynchronously.
var tag = document.createElement('script');

tag.src = "https://www.youtube.com/iframe_api";
var firstScriptTag = document.getElementsByTagName('script')[0];
firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);

// This function creates an <iframe> (and YouTube player)
// after the API code downloads.
var player;
function onYouTubeIframeAPIReady() {
	player = new YT.Player('player', {
		events: {
			'onReady': onPlayerReady,
			'onStateChange': onPlayerStateChange
		}
		});
}

// Autoplay
function onPlayerReady(event){
	event.target.playVideo();
}

// Submit the form when video is done
function onPlayerStateChange(event) {
if (event.data == YT.PlayerState.ENDED) {
	document.getElementById("next").value = 1; 
	document.forms["nextbutton"].submit();
}
}
