vcl 4.1;
import std;

backend default {
    .host = "nginx";
    .port = "80";
}

# Add hostnames, IP addresses and subnets that are allowed to purge content
acl purge {
    "localhost";
    "127.0.0.1";
    "::1";
    "php-drupal";
}

sub vcl_recv {
  if (req.method == "PURGE") {
    if (!client.ip ~ purge) {
      return (synth(405, client.ip + " is not allowed to send PURGE requests."));
    }
    return (purge);
  }
  if (req.method != "GET" && req.method != "HEAD") {
    return (pass);
  }

 if ( ( req.url ~ "/node/.+/edit") ||
   ( req.url ~ "/node/add") ||
   ( req.url ~ "/admin") ||
   ( req.url ~ "/user")) {
    return (pass);
  }

  unset req.http.Cookie;
  unset req.http.x-cache;
}

sub vcl_deliver {
    # Cleanup of headers
    # unset resp.http.x-url;
    # unset resp.http.x-host;
    # unset req.http.X-Static-File;
   unset resp.http.X-Url;
   unset resp.http.X-Host;
   unset resp.http.X-Drupal-Cache-Tags;

    if (obj.hits > 0) {
      set resp.http.X-Varnish-Cache = "HIT";
    }
    else {
      set resp.http.X-Varnish-Cache = "MISS";
    }
    set resp.http.X-Obj-Hits = obj.hits;
    set resp.http.X-Test = "Hello6667";
}

sub vcl_backend_response {
  set beresp.http.X-Url = bereq.url;
  set beresp.http.X-Host = bereq.http.host;
}
