FROM occitech/magento:php5.5-apache

ENV MAGENTO_VERSION 1.9.2.4

RUN cd /tmp && \
    curl https://codeload.github.com/OpenMage/magento-mirror/tar.gz/$MAGENTO_VERSION -o $MAGENTO_VERSION.tar.gz && \
    tar xvf $MAGENTO_VERSION.tar.gz && \
    mv magento-mirror-$MAGENTO_VERSION/* magento-mirror-$MAGENTO_VERSION/.htaccess /var/www/htdocs

RUN chown -R www-data:www-data /var/www/htdocs

RUN apt-get update && apt-get install -y mysql-client-5.5 libxml2-dev
RUN docker-php-ext-install soap

COPY ./dev-environment/pipeline /opt/pipeline
COPY ./dev-environment/bin/install-magento /usr/local/bin/install-magento
RUN chmod +x /usr/local/bin/install-magento

COPY ./dev-environment/sampledata/magento-sample-data-1.9.2.4.tar.gz /opt/
COPY ./dev-environment/bin/install-sampledata-1.9 /usr/local/bin/install-sampledata
RUN chmod +x /usr/local/bin/install-sampledata && chmod +x /opt/pipeline

RUN bash -c 'bash < <(curl -s -L https://raw.github.com/colinmollenhour/modman/master/modman-installer)'
RUN mv ~/bin/modman /usr/local/bin
#RUN install-magento
#RUN install-sample
VOLUME /var/www/htdocs
RUN sed -i 's/DocumentRoot \/var\/www\/html/DocumentRoot \/var\/www\/htdocs/' /etc/apache2/sites-available/000-default.conf
RUN sed -i 's/DocumentRoot \/var\/www\/html/DocumentRoot \/var\/www\/htdocs/' /etc/apache2/sites-available/default-ssl.conf