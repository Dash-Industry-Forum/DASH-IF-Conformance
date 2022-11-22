/* Requires the Docker Pipeline plugin */
pipeline {
    agent {dockerfile true}
    stages {
        stage('prepareFolder'){
            steps {
              sh 'rm -rf Utils/sessions/*'
            }
        }
        stage('runLocalHLS'){
            steps {
              sh 'php Utils/Process_cli.php  -d -c -w -H -D -l -i -o -s -c -S http://192.168.1.42:8000/index.m3u8'
            }
        }
        /*
        stage('runWaveChecks'){
            steps {
              sh 'cat functional-tests/cta/wave.json |  grep mpd | cut -d ":" -f 2,3 | sed -e "s/,//" | shuf | head -n 3 | xargs -n1 php Utils/Process_cli.php  -d -c -w -H -D -l -i -o -s -c -S'
            }
        }
        stage('runDASHChecks'){
            steps {
              sh 'cat functional-tests/dashif/dashjs.json |  grep mpd | cut -d ":" -f 2,3 | sed -e "s/,//" | grep -v "media.axprod.net" | grep -v "axtest" | grep -v "livesim" | shuf | head -n 3 | xargs -n1 php Utils/Process_cli.php  -d -c -w -H -D -l -i -o -s -c -S'
            }
        }
        */
        /*
        stage('test') {
            steps {
              sh 'rm -rf Utils/sessions/*'
              sh 'rm -rf xml-reports-functional-tests'
              sh 'rm -rf html-reports-functional-tests'
              sh 'php vendor/bin/phpunit -c phpunit.functional-coverage.xml'
            }
        }
        */
    }
}
