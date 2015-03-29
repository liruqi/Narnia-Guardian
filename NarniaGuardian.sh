
find $1/wp-content/uploads -name '*.php' | xargs rm -v
find $1 -size  0 -print0 | xargs -0 rm -v
php NarniaGuardian.php $1 
