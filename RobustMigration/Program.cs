/*
 * Copyright (c) 2010 Open Metaverse Foundation
 * All rights reserved.
 *
 * - Redistribution and use in source and binary forms, with or without 
 *   modification, are permitted provided that the following conditions are met:
 *
 * - Redistributions of source code must retain the above copyright notice, this
 *   list of conditions and the following disclaimer.
 * - Neither the name of the openmetaverse.org nor the names 
 *   of its contributors may be used to endorse or promote products derived from
 *   this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" 
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE 
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE 
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE 
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR 
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF 
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS 
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN 
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) 
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE 
 * POSSIBILITY OF SUCH DAMAGE.
 */

using System;

namespace RobustMigration
{
    class Program
    {
        static void Main(string[] args)
        {
            bool printHelp = false;
            bool printVersion = false;

            string connectionString = null;
            string userUrl = null;
            string inventoryUrl = null;
            string assetUrl = null;

            #region Command Line Argument Handling

            Mono.Options.OptionSet set = new Mono.Options.OptionSet()
            {
                { "c=|connection=", "OpenSim database connection string (ex. \"Data Source=localhost;Database=opensim;User ID=opensim;Password=opensim;\")", v => connectionString = v },
                { "u=|user=", "SimianGrid user service URL (ex. http://localhost/Grid/)", v => userUrl = v },
                { "i=|inventory=", "SimianGrid inventory service URL (ex. http://localhost/Grid/)", v => inventoryUrl = v },
                { "a=|asset=", "SimianGrid asset service URL (ex. http://localhost/Grid/)", v => assetUrl = v },
                { "h|?|help", "Show this help", v => printHelp = true },
                { "v|version", "Show version information", v => printVersion = true }
            };
            set.Parse(args);

            if (String.IsNullOrEmpty(connectionString) || String.IsNullOrEmpty(userUrl) || String.IsNullOrEmpty(inventoryUrl) || String.IsNullOrEmpty(assetUrl))
                printHelp = true;

            if (printHelp || printVersion)
            {
                string version = System.Reflection.Assembly.GetExecutingAssembly().GetName().Version.ToString();
                Console.WriteLine("OpenSim Robust to SimianGrid database migration tool version " + version);
                Console.WriteLine("part of SimianGrid, an Open Metaverse Foundation project");
                Console.WriteLine("Written by John Hurliman, Intel Corporation");
                Console.WriteLine("Distributed under the BSD license");

                if (printHelp)
                    Console.WriteLine();
                else
                    Environment.Exit(0);
            }

            if (printHelp)
            {
                set.WriteOptionDescriptions(Console.Out);
                Environment.Exit(0);
            }

            #endregion Command Line Argument Handling

            Console.WriteLine("Starting user migrations");
            UserMigration users = new UserMigration(connectionString, userUrl);
            Console.WriteLine();
            Console.WriteLine("Starting asset migrations");
            AssetMigration assets = new AssetMigration(connectionString, assetUrl);
            Console.WriteLine();
            Console.WriteLine("Starting inventory migrations");
            InventoryMigration inventories = new InventoryMigration(connectionString, inventoryUrl, userUrl);
            Console.WriteLine();
            Console.WriteLine("Starting friend migrations");
            FriendMigration friends = new FriendMigration(connectionString, userUrl);
            Console.WriteLine();
            Console.WriteLine("Done.");
        }
    }
}
