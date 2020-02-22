var ERROR_HTML = "<div class='status-message'><span>Error Getting Data</span></div>";
var NO_DATA_HTML = "<div class='status-message'><span>Nothing Here</span></div>";

// Taken from https://stackoverflow.com/questions/610406/javascript-equivalent-to-printf-string-format
if (!String.prototype.format)
{
    String.prototype.format = function()
    {
        var args = arguments;
        return this.replace(/{(\d+)}/g, (match, number) =>
        {
            return (typeof args[number] != "undefined" ? args[number] : match);
        });
    };
}

// Taken from https://davidwalsh.name/query-string-javascript
function getQueryStringValue(key)
{
    key = key.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
    var regex = new RegExp('[\\?&]' + key + '=([^&#]*)');
    var results = regex.exec(location.search);

    return (results === null ?
        null : decodeURIComponent(results[1].replace(/\+/g, ' ')));
}

// Taken from https://stackoverflow.com/questions/7342957/how-do-you-round-to-1-decimal-place-in-javascript
function round(value, precision)
{
    var multiplier = Math.pow(10, precision || 0);
    return Math.round(value * multiplier) / multiplier;
}

function dbTimeToLocal(time)
{
    return moment.utc(time, "YYYY-MM-DD HH:mm:ss").tz(configTimeZone);
}