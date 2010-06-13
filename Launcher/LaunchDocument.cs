using System;
using System.IO;
using OpenMetaverse.StructuredData;

namespace VWRAPLauncher
{
    public class LaunchDocument
    {
        public string AccountName;
        public string Name;
        public string LoginUrl;
        public string Region;
        public bool IsLoginUrlCapability;

        public string FirstName
        {
            get
            {
                if (!String.IsNullOrEmpty(Name) && Name.Contains(" "))
                    return Name.Substring(0, Name.IndexOf(' '));
                return String.Empty;
            }
        }

        public string LastName
        {
            get
            {
                if (!String.IsNullOrEmpty(Name) && Name.Contains(" "))
                    return Name.Substring(Name.IndexOf(' ') + 1);
                return String.Empty;
            }
        }

        public static LaunchDocument FromFile(string path)
        {
            try
            {
                using (FileStream stream = new FileStream(path, FileMode.Open, FileAccess.Read))
                {
                    OSDMap launchMap = OSDParser.Deserialize(stream) as OSDMap;

                    if (launchMap != null)
                    {
                        LaunchDocument document = new LaunchDocument();

                        document.LoginUrl = launchMap["loginurl"].AsString();

                        // Not a valid launch doc without a loginurl
                        if (String.IsNullOrEmpty(document.LoginUrl))
                            return null;

                        document.Region = launchMap["region"].AsString();

                        OSDMap authenticatorMap = launchMap["authenticator"] as OSDMap;
                        if (authenticatorMap != null)
                        {
                            document.IsLoginUrlCapability = (authenticatorMap["type"].AsString() == "capability");
                        }

                        OSDMap identifierMap = launchMap["identifier"] as OSDMap;
                        if (identifierMap != null)
                        {
                            document.AccountName = launchMap["account_name"].AsString();
                            document.Name = launchMap["name"].AsString();

                            // Legacy support
                            if (String.IsNullOrEmpty(document.Name))
                            {
                                string first = launchMap["first_name"].AsString();
                                string last = launchMap["last_name"].AsString();

                                document.Name = (first + " " + last).Trim();
                            }
                        }

                        return document;
                    }
                }
            }
            catch
            {
            }

            return null;
        }
    }
}
