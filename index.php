<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ISSET Tsévié - Système de Gestion Scolaire</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-blue-100 flex items-center justify-center min-h-screen">

  <div class="bg-white rounded-2xl shadow-lg p-8 md:mx-12 md:px-12 flex flex-col md:flex-row items-center justify-between w-11/12 md:w-full lg:w-full">
    
    <!-- Texte -->
    <div class="md:w-1/2 space-y-4">
      <h1 class="text-3xl md:text-4xl font-extrabold text-gray-900">
        ISSET TSÉVIÉ
      </h1>
      <h2 class="text-xl font-semibold text-blue-600 flex items-center gap-2">
        Système de Gestion Scolaire
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z" />
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422A12.083 12.083 0 0118 20.944V21l-6-3-6 3v-.056a12.083 12.083 0 01-.16-10.366L12 14z" />
        </svg>
      </h2>
      <p class="text-gray-600 leading-relaxed">
        Gérez efficacement les étudiants, enseignants et départements de l’Institut Supérieur des Sciences Économiques et Techniques de Tsévié grâce à une plateforme moderne, intuitive et sécurisée.
      </p>

      <a href="/isset/login.php" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-5 py-3 rounded-lg shadow-md transition">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7" />
        </svg>
        Veuillez vous connecter
      </a>
    </div>

    <!-- Image -->
    <div class="md:w-1/2 mt-8 md:mt-0 flex justify-center">
      <img src="images/home.jpg" 
           alt="École ISSET Tsévié" 
           class="rounded-xl shadow-md w-full object-cover">
    </div>
  </div>

</body>
</html>
