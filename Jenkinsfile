// Pipeline da aplicação — um só arquivo para as quatro branches (Multibranch Pipeline).
// A branch decide o ambiente: dev → dev, homologacao → homologacao, pre-producao → pre-producao,
// main → producao (com aprovação manual). Outras branches só constroem e testam.
pipeline {
  agent any

  options {
    timestamps()
    disableConcurrentBuilds()
    buildDiscarder(logRotator(numToKeepStr: '15'))
  }

  environment {
    IMAGEM     = 'reposicao-devops'
    TAG        = "${env.BRANCH_NAME}-${env.BUILD_NUMBER}"
    KUBECONFIG = '/kube/config'
  }

  stages {
    stage('Ambiente') {
      steps {
        script {
          def mapa = ['dev': 'dev', 'homologacao': 'homologacao', 'pre-producao': 'pre-producao', 'main': 'producao']
          env.AMBIENTE = mapa[env.BRANCH_NAME] ?: ''
          echo "Branch ${env.BRANCH_NAME} → ambiente: ${env.AMBIENTE ?: 'nenhum (só build e testes)'}"
        }
      }
    }

    stage('Build da imagem') {
      steps {
        sh 'docker build -t $IMAGEM:$TAG .'
      }
    }

    stage('Testes') {
      steps {
        // 1) sintaxe de todos os arquivos PHP, dentro da própria imagem gerada
        sh '''
          docker run --rm --entrypoint sh $IMAGEM:$TAG -c \
            'for f in $(find /var/www/html -name "*.php"); do php -l "$f" || exit 1; done'
        '''
        // 2) teste de fumaça: o contêiner liga e a página de saúde responde
        sh '''
          ID=$(docker run -d $IMAGEM:$TAG)
          trap 'docker rm -f $ID >/dev/null' EXIT
          for i in $(seq 1 30); do
            if docker exec $ID curl -fs http://localhost/saude.php; then exit 0; fi
            sleep 1
          done
          echo "a aplicação não respondeu"; exit 1
        '''
      }
    }

    stage('Carregar no cluster') {
      when { expression { env.AMBIENTE } }
      steps {
        sh 'kind load docker-image $IMAGEM:$TAG --name devops'
      }
    }

    stage('Aprovação') {
      when { branch 'main' }
      steps {
        timeout(time: 30, unit: 'MINUTES') {
          input message: "Publicar ${env.TAG} em PRODUÇÃO?", ok: 'Publicar'
        }
      }
    }

    stage('Publicar') {
      when { expression { env.AMBIENTE } }
      steps {
        sh 'k8s/deploy.sh $AMBIENTE $TAG'
        sh 'kubectl -n $AMBIENTE get pods -o wide'
      }
    }
  }

  post {
    success { echo "✓ ${env.TAG} ${env.AMBIENTE ? 'publicada em ' + env.AMBIENTE : 'construída e testada'}" }
    failure { echo "✗ falhou — nada foi publicado" }
  }
}
