<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Movie Explorer</title>
    <link rel="stylesheet" href="CSS/Movie.css">
</head>
<body>
    <!-- Navigation (Same as dashboard.html) -->
    <ul>
        <li><a href="dashboard.php">Home</a></li>
        <li><a class="active" href="movies.php">Movies</a></li>
        <li><a href="profile.php">Profile</a></li>
        <li class="logout" style="float:right;"><a href="logout.php">Logout</a></li>
    </ul>

    <!-- Search & Filters -->
    <div class="movie-controls">
        <div class="search-container">
            <input type="text" id="searchInput" class="search-input" placeholder="Search movies..." value="movie">
        </div>
        <div class="filter-container">
            <select id="typeFilter">
                <option value="">All Types</option>
                <option value="movie">Movies</option>
                <option value="series">TV Shows</option>
            </select>
        </div>
        <div class="filter-container">
            <select id="yearFilter">
                <option value="">All Years</option>
                <option value="2023">2023</option>
                <option value="2022">2022</option>
                <option value="2021">2021</option>
            </select>
        </div>
    </div>

    <!-- Movie Grid -->
    <div class="movie-grid" id="movieGrid">
        <div class="loading">Loading movies...</div>
    </div>

    <script>
        // API Configuration
        const OMDB_API_KEY = "9cf7c4c9"; // Your API key
        let currentSearch = "movie"; // Default search
        let currentPage = 1;
        
        // DOM Elements
        const movieGrid = document.getElementById('movieGrid');
        const searchInput = document.getElementById('searchInput');
        const typeFilter = document.getElementById('typeFilter');
        const yearFilter = document.getElementById('yearFilter');
        
        // Fetch movies from OMDB API
        async function fetchMovies() {
            movieGrid.innerHTML = '<div class="loading">Loading movies...</div>';
            
            try {
                const type = typeFilter.value;
                const year = yearFilter.value;
                
                const url = `https://www.omdbapi.com/?s=${encodeURIComponent(currentSearch)}&page=${currentPage}&apikey=${OMDB_API_KEY}${type ? `&type=${type}` : ''}${year ? `&y=${year}` : ''}`;
                
                const response = await fetch(url);
                const data = await response.json();
                
                if (data.Response === "True") {
                    displayMovies(data.Search);
                } else {
                    throw new Error(data.Error || "No movies found");
                }
            } catch (error) {
                movieGrid.innerHTML = `<div class="loading">${error.message}</div>`;
                console.error("API Error:", error);
            }
        }
        
        // Display movies in grid
        function displayMovies(movies) {
            movieGrid.innerHTML = '';
            
            movies.forEach(movie => {
                const movieCard = document.createElement('div');
                movieCard.className = 'movie-card';
                movieCard.innerHTML = `
                    <img src="${movie.Poster !== "N/A" ? movie.Poster : 'https://via.placeholder.com/180x270?text=No+Poster'}" 
                         alt="${movie.Title}" 
                         class="movie-poster">
                    <div class="movie-info">
                        <div class="movie-title">${movie.Title}</div>
                        <div class="movie-meta">
                            <span>${movie.Year}</span>
                            <span>${movie.Type}</span>
                        </div>
                    </div>
                `;
                movieGrid.appendChild(movieCard);
            });
        }
        
        // Event Listeners
        searchInput.addEventListener('input', (e) => {
            currentSearch = e.target.value.trim() || "movie";
            currentPage = 1;
            fetchMovies();
        });
        
        typeFilter.addEventListener('change', fetchMovies);
        yearFilter.addEventListener('change', fetchMovies);
        
        // Initialize
        fetchMovies();
    </script>
</body>
</html>
